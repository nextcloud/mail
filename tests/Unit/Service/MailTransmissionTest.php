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
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\SMTP\SmtpClientFactory;
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
		$this->attachmentService = $this->createMock(IAttachmentService::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->transmission = new MailTransmission(
			$this->userFolder,
			$this->attachmentService,
			$this->imapClientFactory,
			$this->smtpClientFactory,
			$this->eventDispatcher,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->logger
		);
	}

	public function testSendNewMessage() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
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
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
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

	public function testSendNewMessageWithCloudAttachments() {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('testuser');
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
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
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
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
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$account->method('getMailAccount')->willReturn($mailAccount);
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
			->method('findSpecial')
			->with($account, 'drafts')
			->willReturn($draftsMailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with($client, $draftsMailbox, $this->anything())
			->willReturn(13);

		[,,$newId] = $this->transmission->saveDraft($messageData);

		$this->assertEquals(13, $newId);
	}
}
