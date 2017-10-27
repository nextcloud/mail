<?php

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

use Horde_Imap_Client;
use Horde_Mail_Transport;
use OC\Files\Node\File;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Db\Alias;
use OCA\Mail\Mailbox;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Model\ReplyMessage;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\MailTransmission;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Tests\TestCase;
use OCP\Files\Folder;
use PHPUnit_Framework_MockObject_MockObject;

class MailTransmissionTest extends TestCase {

	/** @var AddressCollector|PHPUnit_Framework_MockObject_MockObject */
	private $addressCollector;

	/** @var Folder|PHPUnit_Framework_MockObject_MockObject */
	private $userFolder;

	/** @var IAttachmentService|PHPUnit_Framework_MockObject_MockObject */
	private $attachmentService;

	/** @var SmtpClientFactory|PHPUnit_Framework_MockObject_MockObject */
	private $clientFactory;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var MailTransmission */
	private $transmission;

	protected function setUp() {
		parent::setUp();

		$this->addressCollector = $this->createMock(AddressCollector::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->attachmentService = $this->createMock(IAttachmentService::class);
		$this->clientFactory = $this->createMock(SmtpClientFactory::class);
		$this->logger = $this->createMock(Logger::class);

		$this->transmission = new MailTransmission($this->addressCollector, $this->userFolder, $this->attachmentService, $this->clientFactory, $this->logger);
	}

	public function testSendNewMessage() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$replyData = new RepliedMessageData($account, null, null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, null);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList());

		$this->transmission->sendMessage('garfield', $messageData, $replyData);
	}

	public function testSendNewMessageAndCollectAddresses() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@domain.tld', 'cc@domain.tld', 'bcc@domain.tld', 'sub', 'bod', null);
		$replyData = new RepliedMessageData($account, null, null);
		$message = $this->createMock(IMessage::class);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, null);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList([
				new Address('To', 'to@domain.tld'),
		]));
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList([
				new Address('Cc', 'cc@domain.tld'),
		]));
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList([
				new Address('Bcc', 'bcc@domain.tld'),
		]));
		$this->addressCollector->expects($this->once())
			->method('addAddresses')
			->with($this->equalTo(new AddressList([
					new Address('To', 'to@domain.tld'),
					new Address('Cc', 'cc@domain.tld'),
					new Address('Bcc', 'bcc@domain.tld'),
		])));

		$this->transmission->sendMessage('garfield', $messageData, $replyData);
	}

	public function testSendMessageAndDeleteDraft() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$replyData = new RepliedMessageData($account, null, null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, 123);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList());

		$this->transmission->sendMessage('garfield', $messageData, $replyData, null, 123);
	}

	public function testSendMessageFromAlias() {
		$account = $this->createMock(Account::class);
		$alias = $this->createMock(Alias::class);
		$alias->alias = 'a@d.com';
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$replyData = new RepliedMessageData($account, null, null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, null);
		$account->expects($this->once())
			->method('getName')
			->willReturn('User');
		$account->expects($this->once())
			->method('setAlias')
			->with($alias);
		$message->expects($this->once())
			->method('setFrom')
			->with($this->equalTo(new AddressList([new Address('User', 'a@d.com')])));
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList());

		$this->transmission->sendMessage('garfield', $messageData, $replyData, $alias);
	}

	public function testSendNewMessageWithCloudAttachments() {
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
		$replyData = new RepliedMessageData($account, null, null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, null);
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
		$message->expects($this->once())
			->method('addAttachmentFromFiles')
			->with($node);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList());

		$this->transmission->sendMessage('garfield', $messageData, $replyData);
	}

	public function testReplyToAnExistingMessage() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$folderId = base64_encode('INBOX');
		$repliedMessageId = 321;
		$replyData = new RepliedMessageData($account, $folderId, $repliedMessageId);
		$message = $this->createMock(ReplyMessage::class);
		$account->expects($this->once())
			->method('newReplyMessage')
			->willReturn($message);
		$mailbox = $this->createMock(Mailbox::class);
		$account->expects($this->exactly(2)) // once to get the orignal message and once to flag it
			->method('getMailbox')
			->with(base64_decode($folderId))
			->willReturn($mailbox);
		$repliedMessage = $this->createMock(IMessage::class);
		$mailbox->expects($this->once())
			->method('getMessage')
			->with($repliedMessageId)
			->willReturn($repliedMessage);
		$message->expects($this->once())
			->method('setRepliedMessage')
			->with($repliedMessage);
		$transport = $this->createMock(Horde_Mail_Transport::class);
		$this->clientFactory->expects($this->once())
			->method('create')
			->with($account)
			->willReturn($transport);
		$account->expects($this->once())
			->method('sendMessage')
			->with($message, $transport, null);
		$mailbox->expects($this->once())
			->method('setMessageFlag')
			->with($repliedMessageId, Horde_Imap_Client::FLAG_ANSWERED, true);
		$message->expects($this->once())
			->method('getTo')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getCc')
			->willReturn(new AddressList());
		$message->expects($this->once())
			->method('getBcc')
			->willReturn(new AddressList());

		$this->transmission->sendMessage('garfield', $messageData, $replyData);
	}

	public function testSaveDraft() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$account->expects($this->once())
			->method('saveDraft')
			->with($message, null);

		$this->transmission->saveDraft($messageData);
	}

	public function testSaveDraftAndReplaceOldOne() {
		$account = $this->createMock(Account::class);
		$messageData = NewMessageData::fromRequest($account, 'to@d.com', '', '', 'sub', 'bod', null);
		$message = $this->createMock(IMessage::class);
		$account->expects($this->once())
			->method('newMessage')
			->willReturn($message);
		$account->expects($this->once())
			->method('saveDraft')
			->with($message, 123);

		$this->transmission->saveDraft($messageData, 123);
	}

}
