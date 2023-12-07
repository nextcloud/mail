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
use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Listener\SaveSentMessageListener;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\NewMessageData;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SaveSentMessageListenerTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new SaveSentMessageListener(
			$this->mailboxMapper,
			$this->imapClientFactory,
			$this->messageMapper,
			$this->logger
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleMessageSentMailboxNotSet(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			'abc123',
			$draft,
			$message,
			$mail
		);
		$this->mailboxMapper->expects($this->never())
			->method('findById');
		$this->logger->expects($this->once())
			->method('warning');

		$this->listener->handle($event);
	}

	public function testHandleMessageSentMailboxDoesNotExist(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			'abc123',
			$draft,
			$message,
			$mail
		);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(''));
		$this->messageMapper->expects($this->never())
			->method('save');
		$this->logger->expects($this->once())
		->method('error');

		$this->listener->handle($event);
	}

	public function testHandleMessageSentSavingError(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			'abc123',
			$draft,
			$message,
			$mail
		);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($mailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with(
				$this->anything(),
				$mailbox,
				$mail
			)
			->willThrowException(new Horde_Imap_Client_Exception('', 0));
		$this->expectException(ServiceException::class);

		$this->listener->handle($event);
	}

	public function testHandleMessageSent(): void {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(123);
		$account->method('getMailAccount')->willReturn($mailAccount);
		/** @var NewMessageData|MockObject $newMessageData */
		$newMessageData = $this->createMock(NewMessageData::class);
		/** @var IMessage|MockObject $message */
		$message = $this->createMock(IMessage::class);
		/** @var \Horde_Mime_Mail|MockObject $mail */
		$mail = $this->createMock(\Horde_Mime_Mail::class);
		$draft = new Message();
		$draft->setUid(123);
		$event = new MessageSentEvent(
			$account,
			$newMessageData,
			'abc123',
			$draft,
			$message,
			$mail
		);
		$mailbox = new Mailbox();
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($mailbox);
		$this->messageMapper->expects($this->once())
			->method('save')
			->with(
				$this->anything(),
				$mailbox,
				$mail
			);
		$this->logger->expects($this->never())->method('warning');
		$this->logger->expects($this->never())->method('error');

		$this->listener->handle($event);
	}
}
