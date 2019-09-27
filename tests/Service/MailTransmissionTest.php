<?php declare(strict_types=1);

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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use Horde_Mail_Transport;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Mailbox;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

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

	/** @var ILogger|MockObject */
	private $logger;

	/** @var MailTransmission */
	private $transmission;

	protected function setUp() {
		parent::setUp();

		$this->userFolder = $this->createMock(Folder::class);
		$this->attachmentService = $this->createMock(IAttachmentService::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(ILogger::class);

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
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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

		$this->transmission->sendMessage('garfield', $messageData, null);
	}

	public function testSendMessageFromAlias() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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

		$this->transmission->sendMessage('garfield', $messageData, null, $alias);
	}

	public function testSendNewMessageWithCloudAttachments() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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

		$this->transmission->sendMessage('garfield', $messageData, null);
	}

	public function testReplyToAnExistingMessage() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod');
		$folderId = 'INBOX';
		$repliedMessageId = 321;
		$replyData = new RepliedMessageData($account, $folderId, $repliedMessageId);
		$message = new Message();
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$repliedMessage = $this->createMock(IMAPMessage::class);
		$this->messageMapper->expects($this->once())
			->method('find')
			->with($client, $folderId, $repliedMessageId)
			->willReturn($repliedMessage);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->smtpClientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);

		$this->transmission->sendMessage('garfield', $messageData, $replyData);
	}

	public function testSaveDraft() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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

		$newId = $this->transmission->saveDraft($messageData);

		$this->assertEquals(13, $newId);
	}

}
