<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrationServiceTest extends TestCase {
	private const USER_ID = 'test_user';
	private OutputInterface $output;
	private IUser $user;
	private IExportDestination $exportDestination;
	private IImportSource $importSource;
	private ServiceMockObject $serviceMock;
	private AccountMigrationService $migrationService;

	protected function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(OutputInterface::class);
		$this->exportDestination = $this->createMock(IExportDestination::class);
		$this->importSource = $this->createMock(IImportSource::class);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn(self::USER_ID);

		$this->serviceMock = $this->createServiceMock(AccountMigrationService::class);

		$this->serviceMock->getParameter('crypto')
			->method('encrypt')
			->willReturnCallback(function (string $value) {
				return $value . '_encrypted';
			});

		$this->serviceMock->getParameter('crypto')
			->method('decrypt')
			->willReturnCallback(function (string $encryptedValue) {
				if (!str_ends_with($encryptedValue, '_encrypted')) {
					throw new Exception('Invalid encrypted value');
				}
				return substr($encryptedValue, 0, strlen($encryptedValue) - strlen('_encrypted'));
			});

		$this->migrationService = $this->serviceMock->getService();
	}

	public function testScheduleBackgroundJobs(): void {
		$mailAccount1 = new MailAccount();
		$mailAccount1->setId(101);
		$mailAccount1->setUserId(self::USER_ID);
		$account1 = new Account($mailAccount1);

		$mailAccount2 = new MailAccount();
		$mailAccount2->setId(102);
		$mailAccount2->setUserId(self::USER_ID);
		$account2 = new Account($mailAccount2);

		$this->serviceMock->getParameter('accountService')
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account1, $account2]);

		$scheduledIds = [];
		$this->serviceMock->getParameter('accountService')
			->expects(self::exactly(2))
			->method('scheduleBackgroundJobs')
			->willReturnCallback(function (int $accountId) use (&$scheduledIds) {
				$scheduledIds[] = $accountId;
			});

		$this->migrationService->scheduleBackgroundJobs(
			$this->user,
			$this->output,
		);

		self::assertSame([101, 102], $scheduledIds);
	}
}
