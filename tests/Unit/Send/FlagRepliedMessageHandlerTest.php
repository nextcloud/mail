<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Unit\Send;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class FlagRepliedMessageHandlerTest extends TestCase {
	private IMAPClientFactory|MockObject $imapClientFactory;
	private MailboxMapper|MockObject $mailboxMapper;
	private LoggerInterface|MockObject $loggerInterface;
	private MockObject|MessageMapper $messageMapper;
	private FlagRepliedMessageHandler $handler;
	private MockObject|DbMessageMapper $dbMessageMapper;

	protected function setUp(): void {

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->dbMessageMapper = $this->createMock(DbMessageMapper::class);
		$this->handler = new FlagRepliedMessageHandler(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->loggerInterface,
			$this->messageMapper,
			$this->dbMessageMapper,
		);
	}

	public function testProcess(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setInReplyToMessageId('ab123');
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		$dbMessage = new Message();
		$dbMessage->setUid(99);
		$dbMessage->setMailboxId(1);
		$mailbox = new Mailbox();
		$mailbox->setMyAcls('rw');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->willReturn([$dbMessage]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->messageMapper->expects(self::once())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::once())
			->method('update');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessError(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setInReplyToMessageId('ab123');
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		$dbMessage = new Message();
		$dbMessage->setUid(99);
		$dbMessage->setMailboxId(1);
		$mailbox = new Mailbox();
		$mailbox->setMyAcls('rw');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->willReturn([$dbMessage]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->messageMapper->expects(self::once())
			->method('addFlag')
			->willThrowException(new DoesNotExistException(''));
		$this->loggerInterface->expects(self::once())
			->method('warning');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessReadOnly(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setInReplyToMessageId('ab123');
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		$dbMessage = new Message();
		$dbMessage->setUid(99);
		$dbMessage->setMailboxId(1);
		$mailbox = new Mailbox();
		$mailbox->setMyAcls('r');
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->willReturn([$dbMessage]);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->willReturn($mailbox);
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessNotFound(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setInReplyToMessageId('ab123');
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);

		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->willReturn([]);
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessNoRepliedMessageId(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);

		$this->dbMessageMapper->expects(self::never())
			->method('findByMessageId');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->imapClientFactory->expects(self::never())
			->method('getClient');
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage);
	}
}
