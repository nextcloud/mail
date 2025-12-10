<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use function json_decode;
use function json_encode;
use function substr;

class MailAccountMigratorTest extends TestCase {

	private MailAccountMigrator $migrator;

	private ServiceMockObject $serviceMock;
	private OutputInterface|MockObject $output;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(MailAccountMigrator::class);
		$this->serviceMock->getParameter('l10n')
			->method('t')
			->willReturnArgument(0);
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
		$this->migrator = $this->serviceMock->getService();

		$this->output = $this->createMock(OutputInterface::class);
	}

	public function testGetId(): void {
		$id = $this->migrator->getId();

		self::assertEquals('mail_account', $id);
	}

	public function testGetDisplayName(): void {
		$displayName = $this->migrator->getDisplayName();

		self::assertEquals('Mail', $displayName);
	}

	public function testGetDescription(): void {
		$description = $this->migrator->getDisplayName();

		self::assertNotEmpty($description);
	}

	public function testGetVersion(): void {
		$version = $this->migrator->getVersion();

		self::assertGreaterThanOrEqual(01_00_00, $version);
	}

	public function testCantImportNewer(): void {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn(99_00_00);

		$canImport = $this->migrator->canImport($importSource);

		self::assertFalse($canImport);
	}

	public function testCanImportSame(): void {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn($this->migrator->getVersion());

		$canImport = $this->migrator->canImport($importSource);

		self::assertTrue($canImport);
	}

	public function testCanImportOlder(): void {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn($this->migrator->getVersion() - 00_00_01);

		$canImport = $this->migrator->canImport($importSource);

		self::assertTrue($canImport);
	}

	public function testExportBasicAccountInfo(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user_export');
		$account1 = $this->createMock(Account::class);
		$account1->method('getId')->willReturn(101);
		$account1->method('getUserId')->willReturn('user_export');
		$account1->method('jsonSerialize')->willReturn(['id' => 101]);
		$account2 = $this->createMock(Account::class);
		$account2->method('getId')->willReturn(102);
		$account2->method('getUserId')->willReturn('user_export');
		$account2->method('jsonSerialize')->willReturn(['id' => 102]);
		/** @var AccountService|MockObject $accountService */
		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects(self::once())
			->method('findByUserId')
			->with('user_export')
			->willReturn([
				$account1,
				$account2,
			]);
		$exportDestination = $this->createMock(IExportDestination::class);
		$exportDestination->method('addFileContents')
			->willReturnCallback(function (string $path, string $content) {
				if ($path === 'mail/accounts/index.json') {
					self::assertSame(
						[
							101 => 'mail/accounts/101.json',
							102 => 'mail/accounts/102.json',
						],
						json_decode($content, true)
					);
				} elseif ($path === 'mail/accounts/101.json') {
					$accountData = json_decode($content, true);
					self::assertArrayHasKey('id', $accountData);
					self::assertSame(101, $accountData['id']);
				} elseif ($path === 'mail/accounts/102.json') {
					$accountData = json_decode($content, true);
					self::assertArrayHasKey('id', $accountData);
					self::assertSame(102, $accountData['id']);
				} else {
					$this->fail('Invalid file content path ' . $path);
				}
			});

		$this->migrator->export(
			$user,
			$exportDestination,
			$this->output,
		);
	}

	public function testImportBasicAccountInfo(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user_import');
		$accountData = [
			'id' => 101,
			'userId' => 'user_export',
			'name' => 'Jane Doe',
			'email' => 'jane@doe.org',
			'authMethod' => 'password',
			'aliases' => [],
		];
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getFileContents')
			->willReturnMap([
				['mail/accounts/index.json', json_encode([101 => 'mail/accounts/101.json'])],
				['mail/accounts/101.json', json_encode($accountData)],
			]);
		$newAccount = new MailAccount([]);
		$newAccount->setUserId('user_import');
		$newAccount->setName('Jane Doe');
		$newAccount->setAuthMethod('password');
		$newAccount->setEditorMode('plain');
		$newAccount->setClassificationEnabled(false);
		/** @var AccountService|MockObject $accountService */
		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects(self::once())
			->method('save')
			->with(self::equalTo($newAccount))
			->willReturnArgument(0);

		$this->migrator->import(
			$user,
			$importSource,
			$this->output,
		);
	}

}
