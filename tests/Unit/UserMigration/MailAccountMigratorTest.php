<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\UserMigration;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

class MailAccountMigratorTest extends TestCase {
	private const EXPORT_DELEGATIONS = [
		'appConfigMigrationService' => 'exportAppConfiguration',
		'internalAddressesMigrationService' => 'exportInternalAddresses',
		'trustedSendersMigrationService' => 'exportTrustedSenders',
		'textBlocksMigrationService' => 'exportTextBlocks',
		'tagsMigrationService' => 'exportTags',
		'smimeMigrationService' => 'exportCertificates',
		'accountMigrationService' => 'exportAccounts',
		'quickActionsMigrationService' => 'exportQuickActions',
	];
	private const TAGS_MAPPING = [1 => 11];
	private const CERTIFICATES_MAPPING = [2 => 22];
	private const ACCOUNTS_MAPPING = [3 => 33];
	private const MAILBOXES_MAPPING = [4 => 44];

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

		self::assertSame(MailAccountMigrator::VERSION, $version);
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

	public function testExportDelegatesToEveryMigrationService(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');
		$exportDestination = $this->createMock(IExportDestination::class);

		$calls = [];
		foreach (self::EXPORT_DELEGATIONS as $parameter => $method) {
			$this->serviceMock->getParameter($parameter)
				->expects(self::once())
				->method($method)
				->willReturnCallback(function () use (&$calls, $method) {
					$calls[] = $method;
					return [];
				});
		}

		$this->migrator->export($user, $exportDestination, $this->output);

		self::assertSame(array_values(self::EXPORT_DELEGATIONS), $calls);
	}

	public function testImportDelegatesInDependencyOrderAndThreadsMappings(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');
		$importSource = $this->createMock(IImportSource::class);

		$calls = [];
		$this->serviceMock->getParameter('internalAddressesMigrationService')
			->method('importInternalAddresses')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'importInternalAddresses';
			});
		$this->serviceMock->getParameter('trustedSendersMigrationService')
			->method('importTrustedSenders')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'importTrustedSenders';
			});
		$this->serviceMock->getParameter('textBlocksMigrationService')
			->method('importTextBlocks')
			->willReturnCallback(function () use (&$calls): void {
				$calls[] = 'importTextBlocks';
			});
		$this->serviceMock->getParameter('tagsMigrationService')
			->method('importTags')
			->willReturnCallback(function () use (&$calls): array {
				$calls[] = 'importTags';
				return self::TAGS_MAPPING;
			});
		$this->serviceMock->getParameter('smimeMigrationService')
			->method('importCertificates')
			->willReturnCallback(function () use (&$calls): array {
				$calls[] = 'importCertificates';
				return self::CERTIFICATES_MAPPING;
			});

		$this->serviceMock->getParameter('accountMigrationService')
			->expects(self::once())
			->method('importAccounts')
			->willReturnCallback(function ($actualUser, $actualSource, $actualOutput, array $certificatesMapping) use (&$calls): array {
				$calls[] = 'importAccounts';
				self::assertSame(self::CERTIFICATES_MAPPING, $certificatesMapping);
				return ['accounts' => self::ACCOUNTS_MAPPING, 'mailboxes' => self::MAILBOXES_MAPPING];
			});

		$this->serviceMock->getParameter('appConfigMigrationService')
			->expects(self::once())
			->method('importAppConfiguration')
			->willReturnCallback(function ($actualUser, $actualSource, $actualOutput, array $accountsMapping, array $mailboxesMapping) use (&$calls): void {
				$calls[] = 'importAppConfiguration';
				self::assertSame(self::ACCOUNTS_MAPPING, $accountsMapping);
				self::assertSame(self::MAILBOXES_MAPPING, $mailboxesMapping);
			});

		$this->serviceMock->getParameter('quickActionsMigrationService')
			->expects(self::once())
			->method('importQuickActions')
			->willReturnCallback(function ($actualUser,
				$actualSource,
				$actualOutput,
				array $accountsMapping,
				array $mailboxesMapping,
				array $tagsMapping) use (&$calls): void {
				$calls[] = 'importQuickActions';
				self::assertSame(self::ACCOUNTS_MAPPING, $accountsMapping);
				self::assertSame(self::MAILBOXES_MAPPING, $mailboxesMapping);
				self::assertSame(self::TAGS_MAPPING, $tagsMapping);
			});

		$this->serviceMock->getParameter('accountMigrationService')
			->expects(self::once())
			->method('scheduleBackgroundJobs')
			->willReturnCallback(function (array $accountsMapping) use (&$calls): void {
				$calls[] = 'scheduleBackgroundJobs';
				self::assertSame(self::ACCOUNTS_MAPPING, $accountsMapping);
			});

		$this->migrator->import($user, $importSource, $this->output);

		self::assertSame([
			'importInternalAddresses',
			'importTrustedSenders',
			'importTextBlocks',
			'importTags',
			'importCertificates',
			'importAccounts',
			'importAppConfiguration',
			'importQuickActions',
			'scheduleBackgroundJobs',
		], $calls);
	}

	public function testEstimatedExportSizeSumsEveryServiceAndReturnsKib(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');

		$services = [
			'appConfigMigrationService' => 100,
			'internalAddressesMigrationService' => 200,
			'trustedSendersMigrationService' => 300,
			'textBlocksMigrationService' => 400,
			'tagsMigrationService' => 500,
			'smimeMigrationService' => 600,
			'accountMigrationService' => 700,
			'quickActionsMigrationService' => 800,
		];
		foreach ($services as $parameter => $bytes) {
			$this->serviceMock->getParameter($parameter)
				->expects(self::once())
				->method('getEstimatedExportSize')
				->with($user)
				->willReturn($bytes);
		}

		$size = $this->migrator->getEstimatedExportSize($user);

		self::assertSame(ceil(array_sum($services) / 1024), $size);
	}

	public function testEstimatedExportSizeForUserWithoutData(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');

		foreach (self::EXPORT_DELEGATIONS as $parameter => $method) {
			$this->serviceMock->getParameter($parameter)
				->method('getEstimatedExportSize')
				->willReturn(0);
		}

		$size = $this->migrator->getEstimatedExportSize($user);

		self::assertSame(0.0, $size);
	}

	public function testImportRoutesVersionOneArchivesToTheLegacyImporter(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn(MailAccountMigrator::LEGACY_VERSION);

		$this->serviceMock->getParameter('legacyAccountMigrationService')
			->expects(self::once())
			->method('importAccounts')
			->with($user, $importSource, $this->output)
			->willReturn(self::ACCOUNTS_MAPPING);
		$this->serviceMock->getParameter('accountMigrationService')
			->expects(self::once())
			->method('scheduleBackgroundJobs')
			->with(self::ACCOUNTS_MAPPING);

		// the current-format services must stay out of it
		$this->serviceMock->getParameter('accountMigrationService')
			->expects(self::never())
			->method('importAccounts');
		foreach (['appConfigMigrationService', 'tagsMigrationService', 'smimeMigrationService',
			'quickActionsMigrationService'] as $parameter) {
			$this->serviceMock->getParameter($parameter)
				->expects(self::never())
				->method(self::anything());
		}

		$this->migrator->import($user, $importSource, $this->output);
	}

	public function testCanImportVersionOne(): void {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn(MailAccountMigrator::LEGACY_VERSION);

		self::assertTrue($this->migrator->canImport($importSource));
	}

	public function testCanImportArchiveWithoutMailData(): void {
		$importSource = $this->createMock(IImportSource::class);
		$importSource->method('getMigratorVersion')
			->with('mail_account')
			->willReturn(null);

		$canImport = $this->migrator->canImport($importSource);

		self::assertTrue($canImport);
	}

	public function testImportSchedulesBackgroundJobsWhenALaterStepFails(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('jane');

		$this->serviceMock->getParameter('accountMigrationService')
			->method('importAccounts')
			->willReturn(['accounts' => self::ACCOUNTS_MAPPING, 'mailboxes' => self::MAILBOXES_MAPPING]);
		$this->serviceMock->getParameter('quickActionsMigrationService')
			->method('importQuickActions')
			->willThrowException(new ServiceException('quick action import failed'));

		$this->serviceMock->getParameter('accountMigrationService')
			->expects(self::once())
			->method('scheduleBackgroundJobs')
			->with(self::ACCOUNTS_MAPPING);

		$this->expectException(ServiceException::class);

		$this->migrator->import($user, $this->createMock(IImportSource::class), $this->output);
	}
}
