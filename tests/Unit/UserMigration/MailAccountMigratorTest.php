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
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use function substr;

class MailAccountMigratorTest extends TestCase {

	private MailAccountMigrator $migrator;

	/** @var ServiceMockObject<MailAccountMigrator> */
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



}
