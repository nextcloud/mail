<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Listener\UserEnabledDisabledListener;
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UserEnabledDisabledListenerTest extends TestCase {
	private AccountService&MockObject $accountService;
	private LoggerInterface&MockObject $logger;
	private UserEnabledDisabledListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new UserEnabledDisabledListener(
			$this->accountService,
			$this->logger,
		);
	}

	private function createUserMock(string $userId): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($userId);
		return $user;
	}

	private function createAccountMock(int $id): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId($id);
		return new Account($mailAccount);
	}

	public function testHandleUnrelatedEvent(): void {
		$this->accountService->expects($this->never())
			->method('findByUserId');

		$this->listener->handle(new Event());
	}

	public function testHandleUnrelatedFeature(): void {
		$event = new UserChangedEvent($this->createUserMock('user'), 'displayName', 'New name');

		$this->accountService->expects($this->never())
			->method('findByUserId');

		$this->listener->handle($event);
	}

	public function testHandleDisableRemovesJobs(): void {
		$user = $this->createUserMock('test-user');
		$event = new UserChangedEvent($user, 'enabled', false, true);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$this->createAccountMock(1), $this->createAccountMock(2)]);

		$this->accountService->expects($this->never())
			->method('scheduleBackgroundJobs');
		$this->accountService->expects($this->exactly(2))
			->method('removeBackgroundJobs')
			->willReturnCallback(function (int $accountId): void {
				$this->assertContains($accountId, [1, 2]);
			});

		$this->listener->handle($event);
	}

	public function testHandleEnableSchedulesJobs(): void {
		$user = $this->createUserMock('test-user');
		$event = new UserChangedEvent($user, 'enabled', true, false);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$this->createAccountMock(42)]);

		$this->accountService->expects($this->once())
			->method('scheduleBackgroundJobs')
			->with(42);
		$this->accountService->expects($this->never())
			->method('removeBackgroundJobs');

		$this->listener->handle($event);
	}

	public function testHandleSwallowsExceptions(): void {
		$user = $this->createUserMock('test-user');
		$event = new UserChangedEvent($user, 'enabled', false, true);
		$exception = new \RuntimeException('boom');

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$this->createAccountMock(1)]);
		$this->accountService->method('removeBackgroundJobs')
			->willThrowException($exception);

		$this->logger->expects($this->once())
			->method('error')
			->with($this->anything(), ['uid' => 'test-user', 'exception' => $exception]);

		$this->listener->handle($event);
	}
}
