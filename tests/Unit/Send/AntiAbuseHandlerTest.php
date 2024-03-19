<?php
/*
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
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
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Send\AntiAbuseHandler;
use OCA\Mail\Send\SendHandler;
use OCA\Mail\Service\AntiAbuseService;
use OCA\Mail\Service\RecipientsService;
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
		$this->recipientsService = $this->createMock(RecipientsService::class);
		$this->handler = new AntiAbuseHandler(
			$this->userManager,
			$this->antiAbuseService,
			$this->recipientsService,
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

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($this->createMock(IUser::class));
		$this->logger->expects(self::never())
			->method('error');
		$this->antiAbuseService->expects(self::once())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessNoUser(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_RAW);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn(null);
		$this->logger->expects(self::once())
			->method('error');
		$this->antiAbuseService->expects(self::never())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::never())
			->method('process');

		$this->handler->process($account, $localMessage);
	}

	public function testProcessAlreadyProcessed(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setId(123);
		$account = new Account($mailAccount);
		$localMessage = new LocalMessage();
		$localMessage->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);

		$this->userManager->expects(self::never())
			->method('get');
		$this->logger->expects(self::never())
			->method('error');
		$this->antiAbuseService->expects(self::never())
			->method('onBeforeMessageSent');
		$this->sendHandler->expects(self::once())
			->method('process');

		$this->handler->process($account, $localMessage);
	}
}
