<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Command\ClearMailboxCache;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ClearMailboxCacheTest extends TestCase {
	private MailboxMapper|\PHPUnit\Framework\MockObject\MockObject $mailboxMapper;
	private AccountService|\PHPUnit\Framework\MockObject\MockObject $accountService;
	private SyncService|\PHPUnit\Framework\MockObject\MockObject $syncService;
	private ClearMailboxCache $command;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->syncService = $this->createMock(SyncService::class);

		$this->command = new ClearMailboxCache(
			$this->mailboxMapper,
			$this->accountService,
			$this->syncService,
		);
	}

	public function testName(): void {
		$this->assertSame('mail:mailbox:clear-cache', $this->command->getName());
	}

	public function testClearsCache(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);
		$mailbox->setAccountId(1);
		$account = new Account($this->createMock(\OCA\Mail\Db\MailAccount::class));

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->accountService->method('findById')
			->with(1)
			->willReturn($account);
		$this->syncService->expects($this->once())
			->method('clearCache')
			->with($account, $mailbox);

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::SUCCESS, $exitCode);
	}

	public function testFailsWhenMailboxDoesNotExist(): void {
		$this->mailboxMapper->method('findById')
			->with(10)
			->willThrowException(new DoesNotExistException('not found'));
		$this->syncService->expects($this->never())
			->method('clearCache');

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}

	public function testFailsWhenClearingCacheThrows(): void {
		$mailbox = new Mailbox();
		$mailbox->setId(10);
		$mailbox->setAccountId(1);
		$account = new Account($this->createMock(\OCA\Mail\Db\MailAccount::class));

		$this->mailboxMapper->method('findById')
			->with(10)
			->willReturn($mailbox);
		$this->accountService->method('findById')
			->with(1)
			->willReturn($account);
		$this->syncService->method('clearCache')
			->willThrowException($this->createMock(ServiceException::class));

		$tester = new CommandTester($this->command);
		$exitCode = $tester->execute(['mailbox-id' => '10']);

		$this->assertSame(Command::FAILURE, $exitCode);
	}
}
