<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use Horde_Mail_Transport;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox as DbMailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message as DbMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MailTransmissionTest extends TestCase {
	/** @var Folder|MockObject */
	private $userFolder;

	/** @var IAttachmentService|MockObject */
	private $attachmentService;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var SmtpClientFactory|MockObject */
	private $smtpClientFactory;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var MailTransmission */
	private $transmission;

	/** @var AliasesService|MockObject */
	private $aliasService;

	/** @var GroupsIntegration|MockObject */
	private $groupsIntegration;

	private SmimeService $smimeService;

	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->attachmentService = $this->createMock(IAttachmentService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);
		$this->aliasService = $this->createMock(AliasesService::class);
		$this->groupsIntegration = $this->createMock(GroupsIntegration::class);
		$this->smimeService = $this->createMock(SmimeService::class);

		$this->transmission = new MailTransmission(
			$this->userFolder,
			$this->attachmentService,
			$this->mailManager,
			$this->imapClientFactory,
			$this->smtpClientFactory,
			$this->eventDispatcher,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->logger,
			$this->performanceLogger,
			$this->aliasService,
			$this->groupsIntegration,
			$this->smimeService,
		);
	}

	public function testSendNewMessage() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod');
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$this->transmission->sendMessage($messageData, null);
	}

	public function testSendMessageFromAlias() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$alias = new Alias();
		$alias->setAlias('a@d.com');
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod');
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('getName')
			->willReturn('User');
		$account->expects($this->once())
			->method('setAlias')
			->with($alias);

		$this->transmission->sendMessage($messageData, null, $alias);
	}

	public function testSendNewMessageWithMessageAsAttachment() {
		$userId = 'testuser';

		$mailAccount = new MailAccount();
		$mailAccount->setUserId($userId);
		$mailAccount->setSentMailboxId(123);

		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$account->method('getUserId')->willReturn($userId);

		$originalAttachment = [
			[
				'fileName' => 'Test attachment',
				'id' => '123456',
				'type' => 'message'
			]
		];

		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', $originalAttachment);

		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$attachmentMessage = new DbMessage();
		$attachmentMessage->setMailboxId(1234);
		$attachmentMessage->setUid(11);

		$mailbox = new DbMailbox();
		$mailbox->setAccountId(22);
		$mailbox->setName('mock');

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->exactly(2))
			->method('getClient')
			->with($account)
			->willReturn($client);

		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($mailAccount->getUserId(), 123456)
			->willReturn($attachmentMessage);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($mailAccount->getUserId(), $attachmentMessage->getMailboxId())
			->willReturn($mailbox);

		$source = 'da message';

		$this->messageMapper->expects($this->once())
			->method('getFullText')
			->with(
				$this->imapClientFactory->getClient($account),
				$mailbox->getName(),
				11,
				$userId,
			)
			->willReturn($source);

		$this->transmission->sendMessage($messageData, null);
	}

	public function testSendNewMessageWithAttachmentsFromEmail() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);

		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');

		$originalAttachment = [
			[
				'fileName' => 'Test attachment',
				'id' => '2.2',
				'messageId' => '100',
				'type' => 'message-attachment'
			]
		];

		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', $originalAttachment);

		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$attachmentMessage = new DbMessage();
		$attachmentMessage->setMailboxId(1234);
		$attachmentMessage->setUid(11);

		$mailbox = new DbMailbox();
		$mailbox->setAccountId(22);
		$mailbox->setName('mock');

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->exactly(2))
			->method('getClient')
			->with($account)
			->willReturn($client);

		$this->mailManager->expects($this->once())
			->method('getMessage')
			->with($mailAccount->getUserId(), $originalAttachment[0]['messageId'])
			->willReturn($attachmentMessage);
		$this->mailManager->expects($this->once())
			->method('getMailbox')
			->with($mailAccount->getUserId(), $attachmentMessage->getMailboxId())
			->willReturn($mailbox);

		$content = ['blablabla'];
		$this->messageMapper->expects($this->once())
			->method('getRawAttachments')
			->with($this->imapClientFactory->getClient($account), $mailbox->getName(), $attachmentMessage->getUid(), 'testuser',
				[$originalAttachment[0]['id']])
			->willReturn($content);

		$this->transmission->sendMessage($messageData, null);
	}

	public function testSendNewMessageWithCloudAttachments() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$attachmenst = [
			[
				'fileName' => 'cat.jpg',
			],
			[
				'fileName' => 'dog.jpg',
			],
			[] // add an invalid one too
		];
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', $attachmenst);
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$this->userFolder->expects($this->exactly(2))
			->method('nodeExists')
			->willReturnMap([
				['cat.jpg', true],
				['dog.jpg', false],
			]);
		$node = $this->createConfiguredMock(File::class, [
			'getName' => 'cat.jpg',
			'getContent' => 'jhsjdshfjdsh',
			'getMimeType' => 'image/jpeg'
		]);
		$this->userFolder->expects($this->once())
			->method('get')
			->with('cat.jpg')
			->willReturn($node);

		$this->transmission->sendMessage($messageData, null);
	}

	public function testReplyToAnExistingMessage() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod');
		$folderId = 'INBOX';
		$repliedMessageUid = 321;
		$messageInReply = new DbMessage();
		$messageInReply->setUid($repliedMessageUid);
		$messageInReply->setMessageId('message@server');
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$this->transmission->sendMessage($messageData, $messageInReply->getMessageId());
	}

	public function testSaveDraft() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		$mailAccount->setDraftsMailboxId(123);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Test User');
		$account->method('getEMailAddress')->willReturn('test@user');
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod');
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$draftsMailbox = new DbMailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($draftsMailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with($client, $draftsMailbox, $this->anything())
			->willReturn(13);

		[, , $newId] = $this->transmission->saveDraft($messageData);

		$this->assertEquals(13, $newId);
	}

	public function testSendLocalMessage(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(10);
		$mailAccount->setUserId('testuser');
		$mailAccount->setSentMailboxId(123);
		$mailAccount->setName('Emily');
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($mailAccount->getId());
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setAttachments([]);
		$to = Recipient::fromParams([
			'email' => 'emily@stardewvalleypub.com',
			'label' => 'Emily',
			'type' => Recipient::TYPE_TO
		]);
		$message->setRecipients([$to]);

		$alias = Alias::fromParams([
			'id' => 1,
			'accountId' => 10,
			'name' => 'Emily',
			'alias' => 'Emmerlie'
		]);
		$this->aliasService->expects(self::once())
			->method('find')
			->with($message->getAliasId(), $mailAccount->getUserId())
			->willReturn($alias);

		$replyMessage = new DbMessage();
		$replyMessage->setMessageId('abc');

		$this->transmission->sendLocalMessage(new Account($mailAccount), $message);
	}

	public function testConvertInlineImageToAttachment() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setSentMailboxId(100);

		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Bob');
		$account->method('getEMailAddress')->willReturn('bob@bob.internal');

		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);

		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn(new \Horde_Mail_Transport_Null());

		$messageData = NewMessageData::fromRequest(
			$account,
			'alice@alice.internal',
			'',
			'',
			'Nextcloud Logo',
			'<p style="margin:0;">Hello,</p><p style="margin:0;">&nbsp;</p><p style="margin:0;"><img src="data:image/png;base64,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"></p>'
		);

		/** @var MessageSentEvent|null $messageSentEvent */
		$messageSentEvent = null;

		// capture the MessageSentEvent for the final Horde_Mime_Mail instance
		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use (&$messageSentEvent) {
				if ($event instanceof MessageSentEvent) {
					$messageSentEvent = $event;
				}
			});

		// send message
		$this->transmission->sendMessage($messageData, null);

		// something is wrong when $messageSentEvent is null
		$this->assertInstanceOf(MessageSentEvent::class, $messageSentEvent);

		$mimeMail = $messageSentEvent->getMail();
		$rawMessage = $mimeMail->getRaw(false);

		/*
		 * our dummy message contains one inline image.
		 * the expected result is to have an img element with src="cid:123" and
		 * another part for the attachment with Content-Type: image/png
		 */
		$this->assertStringContainsString('img src=3D"cid:', $rawMessage);
		$this->assertStringContainsString('Content-Type: image/png', $rawMessage);
		$this->assertStringContainsString('Content-Disposition: inline', $rawMessage);
	}

	public function testSendLocalDraft(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(10);
		$mailAccount->setUserId('gunther');
		$mailAccount->setName('Gunther');
		$mailAccount->setEmail('gunther@stardewvalley-museum.com');
		$mailAccount->setDraftsMailboxId(123);
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($mailAccount->getId());
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setAttachments([]);
		$to = Recipient::fromParams([
			'email' => 'emily@stardewvalleypub.com',
			'label' => 'Emily',
			'type' => Recipient::TYPE_TO
		]);
		$message->setRecipients([$to]);

		$alias = Alias::fromParams([
			'id' => 1,
			'accountId' => 10,
			'name' => 'Emily',
			'alias' => 'Emmerlie'
		]);

		$replyMessage = new DbMessage();
		$replyMessage->setMessageId('abc');

		$this->transmission->saveLocalDraft(new Account($mailAccount), $message);
	}

	public function testSendLocalDraftNoDraftsMailbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(10);
		$mailAccount->setUserId('gunther');
		$mailAccount->setName('Gunther');
		$mailAccount->setEmail('gunther@stardewvalley-museum.com');
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($mailAccount->getId());
		$message->setAliasId(2);
		$message->setSendAt(123);
		$message->setSubject('subject');
		$message->setBody('message');
		$message->setHtml(true);
		$message->setInReplyToMessageId('abc');
		$message->setAttachments([]);
		$to = Recipient::fromParams([
			'email' => 'emily@stardewvalleypub.com',
			'label' => 'Emily',
			'type' => Recipient::TYPE_TO
		]);
		$message->setRecipients([$to]);

		$alias = Alias::fromParams([
			'id' => 1,
			'accountId' => 10,
			'name' => 'Emily',
			'alias' => 'Emmerlie'
		]);
		$this->aliasService->expects(self::once())
			->method('find')
			->with($message->getAliasId(), $mailAccount->getUserId())
			->willReturn($alias);

		$replyMessage = new DbMessage();
		$replyMessage->setMessageId('abc');

		$this->expectException(ClientException::class);
		$this->transmission->sendLocalMessage(new Account($mailAccount), $message);
	}
}
