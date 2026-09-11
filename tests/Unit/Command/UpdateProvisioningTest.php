<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\UpdateProvisioning;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateProvisioningTest extends TestCase {
	private ProvisioningManager&MockObject $provisioningManager;
	private CommandTester $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->provisioningManager = $this->createMock(ProvisioningManager::class);
		$command = new UpdateProvisioning($this->provisioningManager);
		$command->setHelperSet(new HelperSet([new QuestionHelper()]));
		$this->tester = new CommandTester($command);
	}

	private function existingConfig(): Provisioning {
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

	public function testKeepsUntouchedValues(): void {
		$this->provisioningManager->method('getConfigById')
			->with(3)
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static function (array $data): bool {
				return $data['id'] === 3
					&& $data['imapHost'] === 'imap.example.net'
					&& $data['smtpHost'] === 'smtp.example.com'
					&& $data['emailTemplate'] === '%USERID%@example.com'
					&& $data['masterPasswordEnabled'] === true
					&& $data['masterPassword'] === Provisioning::MASTER_PASSWORD_PLACEHOLDER;
			}));

		$status = $this->tester->execute([
			'id' => '3',
			'--imap-host' => 'imap.example.net',
		]);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testDisablesMasterPassword(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static function (array $data): bool {
				return $data['masterPasswordEnabled'] === false
					&& $data['masterPassword'] === ''
					&& $data['masterUser'] === '';
			}));

		$status = $this->tester->execute([
			'id' => '3',
			'--no-master-password' => true,
		]);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testKeepsSieveDisabledWithoutSieveOptions(): void {
		$config = $this->existingConfig();
		$config->setSieveHost('sieve.example.com');
		$this->provisioningManager->method('getConfigById')
			->willReturn($config);
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['sieveEnabled'] === false));

		$status = $this->tester->execute(['id' => '3']);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testUnknownId(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn(null);
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => '3']);

		self::assertSame(Command::FAILURE, $status);
	}

	public function testReportsInvalidFields(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$exception = new ValidationException();
		$exception->setField('emailTemplate', false);
		$this->provisioningManager->method('updateProvisioning')
			->willThrowException($exception);

		$status = $this->tester->execute([
			'id' => '3',
			'--email-template' => '',
		]);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('emailTemplate', $this->tester->getDisplay());
	}

	/** @dataProvider invalidIds */
	public function testRejectsInvalidIdBeforeUpdating(string $id): void {
		$this->provisioningManager->expects(self::never())
			->method('getConfigById');
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => $id, '--imap-host' => 'imap.example.net']);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('positive integer', $this->tester->getDisplay());
	}

	public static function invalidIds(): array {
		return [[''], ['0'], ['-1'], ['3typo'], ['3.5'], ['3e1'], ['99999999999999999999']];
	}

	/** @dataProvider passwordInputModes */
	public function testUpdatesMasterPasswordFromInput(bool $interactive): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['masterPasswordEnabled'] === true
				&& $data['masterPassword'] === ' new password ' && $data['masterUser'] === '*masteruser'));

		$this->tester->setInputs([' new password ']);
		$status = $this->tester->execute(['id' => '3', '--master-password' => null], ['interactive' => $interactive]);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringNotContainsString('new password', $this->tester->getDisplay());
	}

	public static function passwordInputModes(): array {
		return [[true], [false]];
	}

	public function testRejectsMissingPasswordInput(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => '3', '--master-password' => null], ['interactive' => false]);

		self::assertSame(Command::INVALID, $status);
	}

	/** @dataProvider invalidPorts */
	public function testRejectsInvalidPorts(string $option, string $port): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => '3', $option => $port]);

		self::assertSame(Command::INVALID, $status);
	}

	public static function invalidPorts(): array {
		return [['--imap-port', ''], ['--smtp-port', '587typo'], ['--sieve-port', '65536']];
	}

	public function testRequiresPortWhenEnablingSieve(): void {
		$this->provisioningManager->method('getConfigById')
			->willReturn($this->existingConfig());
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => '3', '--sieve-host' => 'sieve.example.com']);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('sievePort is required', $this->tester->getDisplay());
	}

	public function testKeepsExistingPortWhenEnablingSieve(): void {
		$config = $this->existingConfig();
		$config->setSievePort(4190);
		$this->provisioningManager->method('getConfigById')
			->willReturn($config);
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['sieveEnabled'] === true && $data['sievePort'] === 4190));

		$status = $this->tester->execute(['id' => '3', '--sieve-host' => 'sieve.example.com']);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testCannotClearPortWhileSieveIsEnabled(): void {
		$config = $this->existingConfig();
		$config->setSieveEnabled(true);
		$config->setSievePort(4190);
		$this->provisioningManager->method('getConfigById')
			->willReturn($config);
		$this->provisioningManager->expects(self::never())
			->method('updateProvisioning');

		$status = $this->tester->execute(['id' => '3', '--sieve-port' => '']);

		self::assertSame(Command::INVALID, $status);
	}

	public function testCanDisableSieveAndClearItsPort(): void {
		$config = $this->existingConfig();
		$config->setSieveEnabled(true);
		$config->setSievePort(4190);
		$this->provisioningManager->method('getConfigById')
			->willReturn($config);
		$this->provisioningManager->expects(self::once())
			->method('updateProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['sieveEnabled'] === false && $data['sievePort'] === null));

		$status = $this->tester->execute(['id' => '3', '--no-sieve' => true, '--sieve-port' => '']);

		self::assertSame(Command::SUCCESS, $status);
	}
}
