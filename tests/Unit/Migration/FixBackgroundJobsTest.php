<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Migration\FixBackgroundJobs;
use OCA\Mail\Service\AccountService;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class FixBackgroundJobsTest extends TestCase {
	private MailAccountMapper&MockObject $mapper;
	private AccountService&MockObject $accountService;
	private IUserManager&MockObject $userManager;
	private IOutput&MockObject $output;
	private FixBackgroundJobs $step;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->output = $this->createMock(IOutput::class);

		$this->step = new FixBackgroundJobs(
			$this->mapper,
			$this->accountService,
			$this->userManager,
		);
	}

	private function createAccount(int $id, string $uid): MailAccount {
		$account = new MailAccount();
		$account->setId($id);
		$account->setUserId($uid);
		return $account;
	}

	public function testReconcilesEnabledAndDisabledUsers(): void {
		$enabledAccount = $this->createAccount(1, 'enabled-user');
		$disabledAccount = $this->createAccount(2, 'disabled-user');
		$orphanAccount = $this->createAccount(3, 'missing-user');

		$this->mapper->method('getAllAccounts')
			->willReturn([$enabledAccount, $disabledAccount, $orphanAccount]);

		$enabledUser = $this->createMock(IUser::class);
		$enabledUser->method('isEnabled')->willReturn(true);
		$disabledUser = $this->createMock(IUser::class);
		$disabledUser->method('isEnabled')->willReturn(false);

		$this->userManager->method('get')
			->willReturnMap([
				['enabled-user', $enabledUser],
				['disabled-user', $disabledUser],
				['missing-user', null],
			]);

		$this->accountService->expects($this->once())
			->method('scheduleBackgroundJobs')
			->with(1);
		$removed = [];
		$this->accountService->expects($this->exactly(2))
			->method('removeBackgroundJobs')
			->willReturnCallback(function (int $id) use (&$removed): void {
				$removed[] = $id;
			});

		$this->step->run($this->output);

		$this->assertEqualsCanonicalizing([2, 3], $removed);
	}
}
