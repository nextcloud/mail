<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\Send;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MessageSentEvent;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SendHandlerTest extends TestCase {
	private MockObject|ProtocolFactory $protocolFactory;
	private MockObject|IEventDispatcher $eventDispatcher;
	private MockObject|MailboxMapper $mailboxMapper;
	private MockObject|LoggerInterface $logger;
	private MockObject|FlagRepliedMessageHandler $nextHandler;
	private SendHandler $handler;

	protected function setUp(): void {
		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->nextHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->handler = new SendHandler(
			$this->protocolFactory,
			$this->eventDispatcher,
			$this->mailboxMapper,
			$this->logger,
		);
		$this->handler->setNext($this->nextHandler);
	}

	public function testProcessSkipsIfAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_PROCESSED);

		$this->protocolFactory->expects(self::never())
			->method('transmissionConnector');
		$this->nextHandler->expects(self::once())
			->method('process')
			->with($account, $localMessage)
			->willReturn($localMessage);

		$this->handler->process($account, $localMessage);
	}

	public function testProcessNoSentMailbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		// sentMailboxId is null — no sent mailbox configured
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->protocolFactory->expects(self::never())
			->method('transmissionConnector');
		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');
		$this->nextHandler->expects(self::never())
			->method('process');

		$result = $this->handler->process($account, $localMessage);

		$this->assertEquals(LocalMessage::STATUS_NO_SENT_MAILBOX, $result->getStatus());
	}

	public function testProcessSentMailboxNotFound(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setSentMailboxId(42);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with(42)
			->willThrowException(new DoesNotExistException(''));
		$this->protocolFactory->expects(self::never())
			->method('transmissionConnector');
		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');
		$this->nextHandler->expects(self::never())
			->method('process');

		$result = $this->handler->process($account, $localMessage);

		$this->assertEquals(LocalMessage::STATUS_NO_SENT_MAILBOX, $result->getStatus());
	}

	public function testProcessSendsMessage(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$sentMailbox = new Mailbox();
		$sentMailbox->setId(1);

		$connector = $this->createMock(ITransmissionConnector::class);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($sentMailbox);
		$this->protocolFactory->expects(self::once())
			->method('transmissionConnector')
			->with($account)
			->willReturn($connector);
		$connector->expects(self::once())
			->method('sendMessage')
			->with($account, $localMessage, $sentMailbox)
			->willReturnCallback(function ($acct, $msg, $mbx) {
				$msg->setStatus(LocalMessage::STATUS_PROCESSED);
			});
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::isInstanceOf(MessageSentEvent::class));
		$this->nextHandler->expects(self::once())
			->method('process')
			->willReturn($localMessage);

		$this->handler->process($account, $localMessage);
	}

	public function testProcessSendError(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$sentMailbox = new Mailbox();
		$sentMailbox->setId(1);

		$connector = $this->createMock(ITransmissionConnector::class);
		$this->mailboxMapper->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($sentMailbox);
		$this->protocolFactory->expects(self::once())
			->method('transmissionConnector')
			->with($account)
			->willReturn($connector);
		$connector->expects(self::once())
			->method('sendMessage')
			->willReturnCallback(function ($acct, $msg, $mbx) {
				$msg->setStatus(LocalMessage::STATUS_SMPT_SEND_FAIL);
			});
		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');
		$this->nextHandler->expects(self::never())
			->method('process');

		$result = $this->handler->process($account, $localMessage);

		$this->assertEquals(LocalMessage::STATUS_SMPT_SEND_FAIL, $result->getStatus());
	}
}
