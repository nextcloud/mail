<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\Send;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class FlagRepliedMessageHandlerTest extends TestCase {
	private MailboxMapper|MockObject $mailboxMapper;
	private LoggerInterface|MockObject $loggerInterface;
	private MockObject|MessageMapper $messageMapper;
	private FlagRepliedMessageHandler $handler;
	private MockObject|DbMessageMapper $dbMessageMapper;

	protected function setUp(): void {

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->dbMessageMapper = $this->createMock(DbMessageMapper::class);
		$this->handler = new FlagRepliedMessageHandler(
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
		$this->messageMapper->expects(self::once())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::once())
			->method('update');

		$this->handler->process($account, $localMessage, $client);
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
		$this->messageMapper->expects(self::once())
			->method('addFlag')
			->willThrowException(new DoesNotExistException(''));
		$this->loggerInterface->expects(self::once())
			->method('warning');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage, $client);
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
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessNotFound(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setInReplyToMessageId('ab123');
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->dbMessageMapper->expects(self::once())
			->method('findByMessageId')
			->willReturn([]);
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessNoRepliedMessageId(): void {
		$account = new Account(new MailAccount());
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->dbMessageMapper->expects(self::never())
			->method('findByMessageId');
		$this->mailboxMapper->expects(self::never())
			->method('findById');
		$this->loggerInterface->expects(self::never())
			->method('warning');
		$this->messageMapper->expects(self::never())
			->method('addFlag');
		$this->dbMessageMapper->expects(self::never())
			->method('update');

		$this->handler->process($account, $localMessage, $client);
	}
}
