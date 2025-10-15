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
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Send\CopySentMessageHandler;
use OCA\Mail\Send\FlagRepliedMessageHandler;
use OCA\Mail\Send\SendHandler;
use PHPUnit\Framework\MockObject\MockObject;

class SendHandlerTest extends TestCase {
	private MockObject|IMailTransmission $transmission;
	private MockObject|CopySentMessageHandler $copySentMessageHandler;
	private MockObject|FlagRepliedMessageHandler $flagRepliedMessageHandler;
	private SendHandler $handler;

	protected function setUp(): void {
		$this->transmission = $this->createMock(IMailTransmission::class);
		$this->copySentMessageHandler = $this->createMock(CopySentMessageHandler::class);
		$this->flagRepliedMessageHandler = $this->createMock(FlagRepliedMessageHandler::class);
		$this->handler = new SendHandler($this->transmission);
		$this->handler->setNext($this->copySentMessageHandler)
			->setNext($this->flagRepliedMessageHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->transmission->expects(self::once())
			->method('sendMessage')
			->with($account, $localMessage);
		$this->copySentMessageHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setId(100);
		$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->transmission->expects(self::never())
			->method('sendMessage');
		$this->copySentMessageHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessError(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(1);
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['getStatus']);
		$mock = $localMessage->getMock();
		$mock->setStatus(10);
		$mock->expects(self::any())
			->method('getStatus')
			->willReturn(LocalMessage::STATUS_SMPT_SEND_FAIL);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->transmission->expects(self::once())
			->method('sendMessage');
		$this->copySentMessageHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock, $client);
	}
}
