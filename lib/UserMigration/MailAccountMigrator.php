<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCA\Mail\UserMigration\Service\AppConfigMigrationService;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCA\Mail\UserMigration\Service\LegacyAccountMigrationService;
use OCA\Mail\UserMigration\Service\QuickActionsMigrationService;
use OCA\Mail\UserMigration\Service\SmimeMigrationService;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCA\Mail\UserMigration\Service\TextBlocksMigrationService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class MailAccountMigrator implements IMigrator, ISizeEstimationMigrator {
	public const EXPORT_ROOT = Application::APP_ID;
	public const FILENAME_PLACEHOLDER = '{filename}';

	/** The current archive format. */
	public const VERSION = 2_00_00;

	/**
	 * The only released predecessor. It reported the literal `01_00_00`, which
	 * PHP reads as octal, hence 4096 rather than 10000.
	 */
	public const LEGACY_VERSION = 4096;

	public function __construct(
		private readonly IL10N $l10n,
		private readonly AccountMigrationService $accountMigrationService,
		private readonly AppConfigMigrationService $appConfigMigrationService,
		private readonly InternalAddressesMigrationService $internalAddressesMigrationService,
		private readonly TrustedSendersMigrationService $trustedSendersMigrationService,
		private readonly TextBlocksMigrationService $textBlocksMigrationService,
		private readonly TagsMigrationService $tagsMigrationService,
		private readonly SmimeMigrationService $smimeMigrationService,
		private readonly QuickActionsMigrationService $quickActionsMigrationService,
		private readonly LegacyAccountMigrationService $legacyAccountMigrationService,
	) {
	}

	/**
	 * The interface reports in KiB, the services in bytes.
	 */
	#[\Override]
	public function getEstimatedExportSize(IUser $user): int|float {
		$bytes = $this->appConfigMigrationService->getEstimatedExportSize($user)
			+ $this->internalAddressesMigrationService->getEstimatedExportSize($user)
			+ $this->trustedSendersMigrationService->getEstimatedExportSize($user)
			+ $this->textBlocksMigrationService->getEstimatedExportSize($user)
			+ $this->tagsMigrationService->getEstimatedExportSize($user)
			+ $this->smimeMigrationService->getEstimatedExportSize($user)
			+ $this->accountMigrationService->getEstimatedExportSize($user)
			+ $this->quickActionsMigrationService->getEstimatedExportSize($user);

		return ceil($bytes / 1024);
	}

	#[\Override]
	public function export(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output,
	): void {
		$output->writeln(
			$this->l10n->t(
				'Exporting mail accounts for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$this->appConfigMigrationService->exportAppConfiguration($user, $exportDestination, $output);
		$this->internalAddressesMigrationService->exportInternalAddresses($user, $exportDestination, $output);
		$this->trustedSendersMigrationService->exportTrustedSenders($user, $exportDestination, $output);
		$this->textBlocksMigrationService->exportTextBlocks($user, $exportDestination, $output);
		$this->tagsMigrationService->exportTags($user, $exportDestination, $output);
		$this->smimeMigrationService->exportCertificates($user, $exportDestination, $output);
		$this->accountMigrationService->exportAccounts($user, $exportDestination, $output);
		$this->quickActionsMigrationService->exportQuickActions($user, $exportDestination, $output);
	}

	/**
	 * App configuration and quick actions run last because they reference
	 * account, mailbox and tag ids that only exist after those are imported.
	 *
	 * @throws \JsonException
	 * @throws \OCA\Mail\Exception\ServiceException
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\DB\Exception
	 * @throws \OCP\UserMigration\UserMigrationException
	 */
	#[\Override]
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t(
				'Importing mail accounts for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		if ($this->getArchiveVersion($importSource) === self::LEGACY_VERSION) {
			$this->accountMigrationService->scheduleBackgroundJobs(
				$this->legacyAccountMigrationService->importAccounts($user, $importSource, $output),
				$output,
			);

			return;
		}

		$this->internalAddressesMigrationService->importInternalAddresses($user, $importSource, $output);
		$this->trustedSendersMigrationService->importTrustedSenders($user, $importSource, $output);
		$this->textBlocksMigrationService->importTextBlocks($user, $importSource, $output);
		$migratedTags = $this->tagsMigrationService->importTags($user, $importSource, $output);
		$migratedCertificates = $this->smimeMigrationService->importCertificates($user, $importSource, $output);
		$migratedAccountsAndMailboxes = $this->accountMigrationService->importAccounts($user, $importSource, $output,
			$migratedCertificates);

		try {
			$this->appConfigMigrationService->importAppConfiguration($user, $importSource, $output,
				$migratedAccountsAndMailboxes['accounts'], $migratedAccountsAndMailboxes['mailboxes']);
			$this->quickActionsMigrationService->importQuickActions($user, $importSource, $output,
				$migratedAccountsAndMailboxes['accounts'], $migratedAccountsAndMailboxes['mailboxes'], $migratedTags);
		} finally {
			$this->accountMigrationService->scheduleBackgroundJobs($migratedAccountsAndMailboxes['accounts'], $output);
		}
	}

	private function getArchiveVersion(IImportSource $importSource): ?int {
		try {
			return $importSource->getMigratorVersion($this->getId());
		} catch (UserMigrationException) {
			return null;
		}
	}

	#[\Override]
	public function getId(): string {
		return 'mail_account';
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l10n->t('Mail');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l10n->t('Mail account parameters, aliases and preferences');
	}

	#[\Override]
	public function getVersion(): int {
		return self::VERSION;
	}

	/**
	 * Only a newer archive is refused here: returning false aborts the import of
	 * every other migrator too, so an archive this migrator merely cannot read
	 * is accepted and skipped in import().
	 */
	#[\Override]
	public function canImport(IImportSource $importSource): bool {
		try {
			$version = $importSource->getMigratorVersion($this->getId());
		} catch (UserMigrationException) {
			return false;
		}

		return $version === null || $version <= self::VERSION;
	}

}
