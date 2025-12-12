<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Integration\UserMigration;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use ChristophWurst\Nextcloud\Testing\TestCase;
use ChristophWurst\Nextcloud\Testing\TestUser;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\Server;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;

class MailAccountMigratorIntegrationTest extends TestCase {

	use DatabaseTransaction;
	use TestUser;

	private AccountService $accountService;

	private MailAccountMigrator $migrator;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = Server::get(AccountService::class);
		$this->migrator = Server::get(MailAccountMigrator::class);
	}

	public function testMigrate(): void {
		$sourceUser = $this->createTestUser();
		$destinationUser = $this->createTestUser();
		$mailAccount = new MailAccount([]);
		$mailAccount->setUserId($sourceUser->getUID());
		$this->accountService->save($mailAccount);

		$exportContents = [];
		$exportDestination = $this->createMock(IExportDestination::class);
		$exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $contents) use (&$exportContents) {
				$exportContents[$path] = $contents;
			});
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getFileContents')
			->willReturnCallback(function (string $path) use (&$exportContents) {
				if (!array_key_exists($path, $exportContents)) {
					$availableFiles = join(', ', array_keys($exportContents));
					throw new UserMigrationException("File contents for {$path} not found. Available: {$availableFiles}");
				}
				return $exportContents[$path];
			});

		$output = $this->createMock(OutputInterface::class);

		$this->migrator->export(
			$sourceUser,
			$exportDestination,
			$output,
		);
		$this->migrator->import(
			$destinationUser,
			$importSource,
			$output,
		);

		$destinationAccoutns = $this->accountService->findByUserId($destinationUser->getUID());
		self::assertCount(1, $destinationAccoutns);
	}

}
