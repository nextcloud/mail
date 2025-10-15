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
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\SentMailboxHandler;
use PHPUnit\Framework\MockObject\MockObject;

class SentMailboxHandlerTest extends TestCase {
	private AntiAbuseHandler|MockObject $antiAbuseHandler;
	private SentMailboxHandler $handler;

	protected function setUp(): void {
		$this->antiAbuseHandler = $this->createMock(AntiAbuseHandler::class);
		$this->handler = new SentMailboxHandler();
		$this->handler->setNext($this->antiAbuseHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setSentMailboxId(1);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->antiAbuseHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testNoSentMailbox(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = $this->getMockBuilder(LocalMessage::class);
		$localMessage->addMethods(['setStatus']);
		$mock = $localMessage->getMock();
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$mock->expects(self::once())
			->method('setStatus')
			->with(LocalMessage::STATUS_NO_SENT_MAILBOX);
		$this->antiAbuseHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $mock, $client);
	}
}
