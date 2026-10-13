<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\ProvisionAccounts;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProvisionAccountsTest extends TestCase {
	private ProvisioningManager&MockObject $provisioningManager;
	private CommandTester $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->provisioningManager = $this->createMock(ProvisioningManager::class);
		$this->tester = new CommandTester(new ProvisionAccounts($this->provisioningManager));
	}

	public function testProvision(): void {
		$this->provisioningManager->expects(self::once())
			->method('provision')
			->willReturn(42);

		$status = $this->tester->execute([]);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringContainsString('42', $this->tester->getDisplay());
	}
}
