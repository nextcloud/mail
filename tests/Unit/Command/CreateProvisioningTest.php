<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\CreateProvisioning;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Service\Provisioning\Manager as ProvisioningManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class CreateProvisioningTest extends TestCase {
	private ProvisioningManager&MockObject $provisioningManager;
	private CommandTester $tester;

	private array $options = [
		'--provisioning-domain' => '*',
		'--email-template' => '%USERID%@example.com',
		'--imap-user' => '%USERID%',
		'--imap-host' => 'imap.example.com',
		'--imap-port' => '993',
		'--imap-ssl-mode' => 'ssl',
		'--smtp-user' => '%USERID%',
		'--smtp-host' => 'smtp.example.com',
		'--smtp-port' => '587',
		'--smtp-ssl-mode' => 'tls',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->provisioningManager = $this->createMock(ProvisioningManager::class);
		$command = new CreateProvisioning($this->provisioningManager);
		$command->setHelperSet(new HelperSet([new QuestionHelper()]));
		$this->tester = new CommandTester($command);
	}

	public function testCreate(): void {
		$provisioning = new Provisioning();
		$provisioning->setId(3);
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with([
				'provisioningDomain' => '*',
				'emailTemplate' => '%USERID%@example.com',
				'imapUser' => '%USERID%',
				'imapHost' => 'imap.example.com',
				'imapPort' => 993,
				'imapSslMode' => 'ssl',
				'smtpUser' => '%USERID%',
				'smtpHost' => 'smtp.example.com',
				'smtpPort' => 587,
				'smtpSslMode' => 'tls',
				'sievePort' => null,
				'sieveEnabled' => false,
				'ldapAliasesProvisioning' => false,
			])
			->willReturn($provisioning);

		$status = $this->tester->execute($this->options);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringContainsString('3', $this->tester->getDisplay());
	}

	public function testCreateWithSieveAndLdapAliases(): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static function (array $data): bool {
				return $data['sieveEnabled'] === true
					&& $data['sieveHost'] === 'sieve.example.com'
					&& $data['sievePort'] === 4190
					&& $data['ldapAliasesProvisioning'] === true
					&& $data['ldapAliasesAttribute'] === 'proxyAddresses';
			}))
			->willReturn(new Provisioning());

		$status = $this->tester->execute(array_merge($this->options, [
			'--sieve-host' => 'sieve.example.com',
			'--sieve-port' => '4190',
			'--sieve-ssl-mode' => 'tls',
			'--ldap-aliases-attribute' => 'proxyAddresses',
		]));

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testCreateWithMasterPassword(): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static function (array $data): bool {
				return $data['masterPasswordEnabled'] === true
					&& $data['masterPassword'] === 'sesame'
					&& $data['masterUser'] === '*masteruser';
			}))
			->willReturn(new Provisioning());

		$status = $this->tester->execute(array_merge($this->options, [
			'--master-password' => 'sesame',
			'--master-user' => '*masteruser',
		]));

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testCreateWithoutMasterPassword(): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static function (array $data): bool {
				return !isset($data['masterPasswordEnabled']);
			}))
			->willReturn(new Provisioning());

		$status = $this->tester->execute($this->options);

		self::assertSame(Command::SUCCESS, $status);
	}

	/** @dataProvider passwordInputs */
	public function testReadsMasterPassword(bool $interactive, string $input, string $expected): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['masterPasswordEnabled'] === true
				&& $data['masterPassword'] === $expected))
			->willReturn(new Provisioning());

		$this->tester->setInputs([$input]);
		$status = $this->tester->execute($this->options + ['--master-password' => null], ['interactive' => $interactive]);

		self::assertSame(Command::SUCCESS, $status);
		self::assertStringNotContainsString($expected, $this->tester->getDisplay());
	}

	public static function passwordInputs(): array {
		return [
			'interactive' => [true, 'sesame', 'sesame'],
			'interactive whitespace' => [true, ' sesame ', ' sesame '],
			'interactive tabs' => [true, "\tsesame\t", "\tsesame\t"],
			'non-interactive' => [false, 'sesame', 'sesame'],
			'non-interactive whitespace' => [false, ' sesame ', ' sesame '],
			'non-interactive CRLF' => [false, " sesame \r", ' sesame '],
		];
	}

	public function testRejectsMissingPasswordInput(): void {
		$this->provisioningManager->expects(self::never())
			->method('newProvisioning');

		$status = $this->tester->execute($this->options + ['--master-password' => null], ['interactive' => false]);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('Could not read master password', $this->tester->getDisplay());
	}

	/** @dataProvider invalidPorts */
	public function testRejectsInvalidPorts(string $option, string $port): void {
		$this->provisioningManager->expects(self::never())
			->method('newProvisioning');

		$status = $this->tester->execute(array_merge($this->options, [$option => $port]));

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('must be an integer between 1 and 65535', $this->tester->getDisplay());
	}

	public static function invalidPorts(): array {
		$cases = [];
		foreach (['--imap-port', '--smtp-port', '--sieve-port'] as $option) {
			foreach (['0', '-1', '65536', '993typo', '1.5', '1e3', '99999999999999999999'] as $port) {
				$cases[$option . '=' . $port] = [$option, $port];
			}
		}
		return $cases;
	}

	/** @dataProvider validPortBoundaries */
	public function testAcceptsPortBoundaries(int $port): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['imapPort'] === $port
				&& $data['smtpPort'] === $port && $data['sievePort'] === $port))
			->willReturn(new Provisioning());

		$status = $this->tester->execute(array_merge($this->options, [
			'--imap-port' => (string)$port,
			'--smtp-port' => (string)$port,
			'--sieve-port' => (string)$port,
			'--sieve-host' => 'sieve.example.com',
		]));

		self::assertSame(Command::SUCCESS, $status);
	}

	public static function validPortBoundaries(): array {
		return [[1], [65535]];
	}

	public function testRequiresPortWhenEnablingSieve(): void {
		$this->provisioningManager->expects(self::never())
			->method('newProvisioning');

		$status = $this->tester->execute($this->options + ['--sieve-host' => 'sieve.example.com']);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('sievePort is required', $this->tester->getDisplay());
	}

	public function testAllowsMissingPortWhenSieveIsDisabled(): void {
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->with(self::callback(static fn (array $data): bool => $data['sieveEnabled'] === false && $data['sievePort'] === null))
			->willReturn(new Provisioning());

		$status = $this->tester->execute($this->options + [
			'--sieve-host' => 'sieve.example.com',
			'--sieve-port' => '',
			'--no-sieve' => true,
		]);

		self::assertSame(Command::SUCCESS, $status);
	}

	public function testRejectsUnknownSslMode(): void {
		$this->provisioningManager->expects(self::never())
			->method('newProvisioning');

		$status = $this->tester->execute(array_merge($this->options, ['--imap-ssl-mode' => 'starttls']));

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('imapSslMode', $this->tester->getDisplay());
	}

	public function testReportsInvalidFields(): void {
		$exception = new ValidationException();
		$exception->setField('imapHost', false);
		$this->provisioningManager->expects(self::once())
			->method('newProvisioning')
			->willThrowException($exception);

		$status = $this->tester->execute($this->options);

		self::assertSame(Command::INVALID, $status);
		self::assertStringContainsString('imapHost', $this->tester->getDisplay());
	}
}
