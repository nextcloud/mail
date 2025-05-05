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
use OCA\Mail\Send\SendHandler;
use OCA\Mail\Service\AntiAbuseService;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AntiAbuseHandlerTest extends TestCase {
	private IUserManager|MockObject $userManager;
	private MockObject|AntiAbuseService $antiAbuseService;
	private LoggerInterface|MockObject $logger;
	private SendHandler|MockObject $sendHandler;
	private AntiAbuseHandler $handler;

	protected function setUp(): void {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->antiAbuseService = $this->createMock(AntiAbuseService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->sendHandler = $this->createMock(SendHandler::class);
		$this->handler = new AntiAbuseHandler(
			$this->userManager,
			$this->antiAbuseService,
			$this->logger,
		);
		$this->handler->setNext($this->sendHandler);
	}

	public function testProcess(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($this->createMock(IUser::class));
		$this->logger->expects(self::never())
			->method('error');
		$this->antiAbuseService->expects(self::once())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessNoUser(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_RAW);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->logger->expects(self::once())
			->method('error');
		$this->antiAbuseService->expects(self::never())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}

	public function testProcessAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->userManager->expects(self::never())
			->method('get');
		$this->logger->expects(self::never())
			->method('error');
		$this->antiAbuseService->expects(self::never())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage, $client);
	}
}
