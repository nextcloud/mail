<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OC\Files\View;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\LocalMailboxMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox as DbMailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message as DbMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\SimpleFS\ISimpleFile;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MailTransmissionTest extends TestCase {

	/** @var Folder|MockObject */
	private $userFolder;

	/** @var AccountService|MockObject */
	private $accountService;

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

	protected function setUp(): void {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->attachmentService = $this->createMock(IAttachmentService::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->performanceLogger = $this->createMock(PerformanceLogger::class);

		$this->transmission = new MailTransmission(
			$this->userFolder,
			$this->accountService,
			$this->attachmentService,
			$this->mailManager,
			$this->imapClientFactory,
			$this->smtpClientFactory,
			$this->eventDispatcher,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->logger,
			$this->performanceLogger
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

	public function testSendLocalMessageWithCloudAttachment() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
		$mailAccount->setSentMailboxId(123);
		/** @var Message|MockObject $message */
		$dbMessage = $this->createMock(IMessage::class);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Lewis');
		$account->method('getEMailAddress')->willReturn('tent-living@stardewvalley.com');
		$account->method('newMessage')->willReturn($dbMessage);
		$dbMessage->method('getFrom')->willReturn(AddressList::fromRow(['label' => $account->getName(), 'email' => $account->getEMailAddress()]));
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt(1643105500);
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'type' => Recipient::TYPE_TO,
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		// cloud attachment
		$attachments = [
			[
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => 1643105500
			]
		];

		$this->userFolder->expects($this->once())
			->method('nodeExists')
			->willReturn(true);
		$this->userFolder->expects($this->once())
			->method('get')
			->willReturn(new File($this->createMock(IRootFolder::class), $this->createMock(View::class), ''));
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->willReturn($this->createMock(Horde_Mail_Transport::class));
		$this->transmission->sendLocalMessage($account, $message, $recipients, $attachments);
	}

	public function testSendLocalMessageWithLocalAttachment() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
		$mailAccount->setSentMailboxId(123);
		/** @var Message|MockObject $message */
		$dbMessage = $this->createMock(IMessage::class);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Lewis');
		$account->method('getEMailAddress')->willReturn('tent-living@stardewvalley.com');
		$account->method('newMessage')->willReturn($dbMessage);
		$dbMessage->method('getFrom')->willReturn(AddressList::fromRow(['label' => $account->getName(), 'email' => $account->getEMailAddress()]));
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt(1643105500);
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'type' => Recipient::TYPE_TO,
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		// local attachment
		$attachments = [
			[
				'id' => 1,
				'type' => 'local',
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => 1643105500
			]
		];

		$this->attachmentService->expects($this->once())
			->method('getAttachment')
			->with($account->getMailAccount()->getUserId(), $attachments[0]['id'])
			->willReturn([$this->createMock(LocalAttachment::class), $this->createMock(ISimpleFile::class) ]);
		$this->transmission->sendLocalMessage($account, $message, $recipients, $attachments);
	}

	public function testSendLocalMessageLocalAttachmentNotFound() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
		$mailAccount->setSentMailboxId(123);
		/** @var Message|MockObject $message */
		$dbMessage = $this->createMock(IMessage::class);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Lewis');
		$account->method('getEMailAddress')->willReturn('tent-living@stardewvalley.com');
		$account->method('newMessage')->willReturn($dbMessage);
		$dbMessage->method('getFrom')->willReturn(AddressList::fromRow(['label' => $account->getName(), 'email' => $account->getEMailAddress()]));
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt(1643105500);
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'type' => Recipient::TYPE_TO,
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		// local attachment
		$attachments = [
			[
				'id' => 1,
				'type' => 'local',
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => 1643105500
			]
		];

		$this->attachmentService->expects($this->once())
			->method('getAttachment')
			->with($account->getMailAccount()->getUserId(), $attachments[0]['id'])
			->willThrowException(new AttachmentNotFoundException());
		$this->transmission->sendLocalMessage($account, $message, $recipients, $attachments);
	}

	public function testSendLocalMessageCloudAttachmentNotFound() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
		$mailAccount->setSentMailboxId(123);
		/** @var Message|MockObject $message */
		$dbMessage = $this->createMock(IMessage::class);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Lewis');
		$account->method('getEMailAddress')->willReturn('tent-living@stardewvalley.com');
		$account->method('newMessage')->willReturn($dbMessage);
		$dbMessage->method('getFrom')->willReturn(AddressList::fromRow(['label' => $account->getName(), 'email' => $account->getEMailAddress()]));
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt(1643105500);
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(false);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'type' => Recipient::TYPE_TO,
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		$attachments = [
			[
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => 1643105500
			]
		];

		$this->userFolder->expects($this->once())
			->method('nodeExists')
			->willReturn(false);
		$this->userFolder->expects($this->never())
			->method('get');
		$this->transmission->sendLocalMessage($account, $message, $recipients, $attachments);
	}

	public function testSendLocalMessageMdn() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
		$mailAccount->setSentMailboxId(123);
		/** @var Message|MockObject $message */
		$dbMessage = $this->createMock(IMessage::class);
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
		$account->method('getName')->willReturn('Lewis');
		$account->method('getEMailAddress')->willReturn('tent-living@stardewvalley.com');
		$account->method('newMessage')->willReturn($dbMessage);
		$dbMessage->method('getFrom')->willReturn(AddressList::fromRow(['label' => $account->getName(), 'email' => $account->getEMailAddress()]));
		$message = new LocalMailboxMessage();
		$message->setAccountId(1);
		$message->setSendAt(1643105500);
		$message->setSubject('Test');
		$message->setBody('Test Test Test');
		$message->setHtml(true);
		$message->setMdn(true);
		$message->setInReplyToMessageId('laskdjhsakjh33233928@startdewvalley.com');
		$recipients = [
			[
				'type' => Recipient::TYPE_TO,
				'label' => 'Gunther',
				'email' => 'museum@startdewvalley.com'
			]
		];
		$attachments = [
			[
				'fileName' => 'SlimesInTheMines.png',
				'mimeType' => 'image/png',
				'createdAt' => 1643105500
			]
		];

		$this->expectException(ServiceException::class);
		$this->transmission->sendLocalMessage($account, $message, $recipients, $attachments);
	}

	public function testSendMessageFromAlias() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('lewis');
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
				11
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
			->with($this->imapClientFactory->getClient($account), $mailbox->getName(), $attachmentMessage->getUid(), [$originalAttachment[0]['id']])
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
		$node = $this->createMock(File::class);
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
		$messageInReply = new \OCA\Mail\Db\Message();
		$messageInReply->setUid($repliedMessageUid);
		$messageInReply->setMessageId('message@server');
		$replyData = new RepliedMessageData($account, $messageInReply);
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$this->transmission->sendMessage($messageData, $replyData);
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
		$draftsMailbox = new \OCA\Mail\Db\Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($draftsMailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with($client, $draftsMailbox, $this->anything())
			->willReturn(13);

		[,,$newId] = $this->transmission->saveDraft($messageData);

		$this->assertEquals(13, $newId);
	}
}
