<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\ListProvisionings;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ListProvisioningsTest extends TestCase {
	private ProvisioningManager&MockObject $provisioningManager;
	private CommandTester $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->provisioningManager = $this->createMock(ProvisioningManager::class);
		$this->tester = new CommandTester(new ListProvisionings($this->provisioningManager));
	}

	private function config(): Provisioning {
		$provisioning = new Provisioning();
		$provisioning->setId(3);
		$provisioning->setProvisioningDomain('example.com');
		$provisioning->setEmailTemplate('%USERID%@example.com');
		$provisioning->setImapUser('%USERID%');
		$provisioning->setImapHost('imap.example.com');
		$provisioning->setImapPort(993);
		$provisioning->setImapSslMode('ssl');
		$provisioning->setSmtpUser('%USERID%');
		$provisioning->setSmtpHost('smtp.example.com');
		$provisioning->setSmtpPort(587);
		$provisioning->setSmtpSslMode('tls');
		$provisioning->setSieveEnabled(false);
		$provisioning->enableMasterPassword('sesame', '*masteruser');

		return $provisioning;
	}

	public function testListEmpty(): void {
		$this->provisioningManager->method('getConfigs')
			->willReturn([]);

		$status = $this->tester->execute([]);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testList(): void {
		$this->provisioningManager->method('getConfigs')
			->willReturn([$this->config()]);

		$status = $this->tester->execute([]);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringContainsString('imap.example.com', $this->tester->getDisplay());
	}

	public function testListJsonHidesMasterPassword(): void {
		$this->provisioningManager->method('getConfigs')
			->willReturn([$this->config()]);

		$status = $this->tester->execute(['--json' => true]);

		self::assertSame(Command::SUCCESS, $status);
		$decoded = json_decode($this->tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
		self::assertSame('example.com', $decoded[0]['provisioningDomain']);
		self::assertSame(Provisioning::MASTER_PASSWORD_PLACEHOLDER, $decoded[0]['masterPassword']);
	}
}
