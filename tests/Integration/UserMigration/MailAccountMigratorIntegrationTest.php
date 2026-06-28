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
use OCA\Mail\Service\AccountService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\Server;

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
	}

}
