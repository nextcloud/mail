<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Listener\UserDeletedListener;
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UserDeletedListenerTest extends TestCase {
	private AccountService&MockObject $accountService;
	private LoggerInterface&MockObject $logger;
	private UserDeletedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new UserDeletedListener(
			$this->accountService,
			$this->logger
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

	public function testImplementsIEventListener(): void {
		$this->assertInstanceOf(\OCP\EventDispatcher\IEventListener::class, $this->listener);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->accountService->expects($this->never())
			->method('findByUserId');

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleUserDeletedWithNoAccounts(): void {
		$user = $this->createUserMock('test-user');
		$event = new UserDeletedEvent($user);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([]);

		$this->accountService->expects($this->never())
			->method('delete');

		$this->logger->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleUserDeletedWithSingleAccount(): void {
		$user = $this->createUserMock('test-user');
		$account = $this->createAccountMock(42);
		$event = new UserDeletedEvent($user);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$account]);

		$this->accountService->expects($this->once())
			->method('delete')
			->with('test-user', 42);

		$this->logger->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleUserDeletedWithMultipleAccounts(): void {
		$user = $this->createUserMock('test-user');
		$account1 = $this->createAccountMock(1);
		$account2 = $this->createAccountMock(2);
		$account3 = $this->createAccountMock(3);
		$event = new UserDeletedEvent($user);

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$account1, $account2, $account3]);

		$this->accountService->expects($this->exactly(3))
			->method('delete')
			->willReturnCallback(function ($userId, $accountId) {
				$this->assertSame('test-user', $userId);
				$this->assertContains($accountId, [1, 2, 3]);
			});

		$this->logger->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleUserDeletedWithClientException(): void {
		$user = $this->createUserMock('test-user');
		$account = $this->createAccountMock(42);
		$event = new UserDeletedEvent($user);

		$exception = new ClientException('Test exception');

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$account]);

		$this->accountService->expects($this->once())
			->method('delete')
			->with('test-user', 42)
			->willThrowException($exception);

		$this->logger->expects($this->once())
			->method('error')
			->with(
				'Could not delete user\'s Mail account: Test exception',
				['exception' => $exception]
			);

		$this->listener->handle($event);
	}

	public function testHandleUserDeletedWithPartialFailure(): void {
		$user = $this->createUserMock('test-user');
		$account1 = $this->createAccountMock(1);
		$account2 = $this->createAccountMock(2);
		$account3 = $this->createAccountMock(3);
		$event = new UserDeletedEvent($user);

		$exception = new ClientException('Failed to delete account 2');

		$this->accountService->expects($this->once())
			->method('findByUserId')
			->with('test-user')
			->willReturn([$account1, $account2, $account3]);

		$this->accountService->expects($this->exactly(3))
			->method('delete')
			->willReturnCallback(function ($userId, $accountId) use ($exception) {
				$this->assertSame('test-user', $userId);
				if ($accountId === 2) {
					throw $exception;
				}
			});

		$this->logger->expects($this->once())
			->method('error')
			->with(
				'Could not delete user\'s Mail account: Failed to delete account 2',
				['exception' => $exception]
			);

		$this->listener->handle($event);
	}
}
