<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Listener\SaveSentMessageListener;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\TwoFactorAdmin\Listener\IListener;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

class SaveSentMessageListenerTest extends TestCase {

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MailboxSync|MockObject */
	private $mailboxSync;

	/** @var ILogger|MockObject */
	private $logger;

	/** @var IListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->listener = new SaveSentMessageListener(
			$this->mailboxMapper,
			$this->imapClientFactory,
			$this->messageMapper,
			$this->mailboxSync,
			$this->logger
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleMessageSentMailboxDoesNotExistCantCreate(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var RepliedMessageData|MockObject $repliedMessageData */
		$repliedMessageData = $this->createMock(RepliedMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			$repliedMessageData,
			$draft,
			$message,
			$mail
		);
		$this->mailboxMapper->expects($this->at(0))
			->method('findSpecial')
			->with($account, 'sent')
			->willThrowException(new DoesNotExistException(''));
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('createMailbox')
			->with(
				'Sent',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_SENT,
					],
				]
			)
			->willThrowException(new \Horde_Imap_Client_Exception());
		$this->logger->expects($this->once())
			->method('logException')
			->with(
				$this->anything(),
				$this->equalTo([
					'message' => 'Could not create sent mailbox: ',
					'level' => ILogger::WARN,
				])
			);

		$this->listener->handle($event);
	}

	public function testHandleMessageSentMailboxDoesNotExist(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var RepliedMessageData|MockObject $repliedMessageData */
		$repliedMessageData = $this->createMock(RepliedMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			$repliedMessageData,
			$draft,
			$message,
			$mail
		);
		$this->mailboxMapper->expects($this->at(0))
			->method('findSpecial')
			->with($account, 'sent')
			->willThrowException(new DoesNotExistException(''));
		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$this->imapClientFactory
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('createMailbox')
			->with(
				'Sent',
				[
					'special_use' => [
						\Horde_Imap_Client::SPECIALUSE_SENT,
					],
				]
			);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->at(1))
			->method('findSpecial')
			->with($account, 'sent')
			->willReturn($mailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with(
				$this->anything(),
				$mailbox,
				$mail
			);

		$this->listener->handle($event);
	}

	public function testHandleMessageSentSavingError(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var RepliedMessageData|MockObject $repliedMessageData */
		$repliedMessageData = $this->createMock(RepliedMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			$repliedMessageData,
			$draft,
			$message,
			$mail
		);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findSpecial')
			->with($account, 'sent')
			->willReturn($mailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with(
				$this->anything(),
				$mailbox,
				$mail
			)
			->willThrowException(new \Horde_Imap_Client_Exception());
		$this->expectException(ServiceException::class);

		$this->listener->handle($event);
	}

	public function testHandleMessageSent(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var RepliedMessageData|MockObject $repliedMessageData */
		$repliedMessageData = $this->createMock(RepliedMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			$repliedMessageData,
			$draft,
			$message,
			$mail
		);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findSpecial')
			->with($account, 'sent')
			->willReturn($mailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with(
				$this->anything(),
				$mailbox,
				$mail
			);

		$this->listener->handle($event);
	}
}
