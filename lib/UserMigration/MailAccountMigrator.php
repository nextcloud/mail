<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class MailAccountMigrator implements IMigrator {
	public const EXPORT_ROOT = Application::APP_ID;
	public const FILENAME_PLACEHOLDER = '{filename}';

	public function __construct(
		private readonly IL10N $l10n,
		private readonly ICrypto $crypto,
		private readonly AccountMigrationService $accountMigrationService,
	) {
	}

	#[\Override]
	public function export(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output,
	): void {
		$output->writeln($this->l10n->t("Exporting mail accounts for user {$user->getUID()}"), OutputInterface::VERBOSITY_VERBOSE);
	}

	#[\Override]
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln($this->l10n->t("Importing mail accounts for user {$user->getUID()}"), OutputInterface::VERBOSITY_VERBOSE);

		$this->accountMigrationService->scheduleBackgroundJobs($user, $output);
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
		return 02_00_00;
	}

	#[\Override]
	public function canImport(IImportSource $importSource): bool {
		try {
			return $importSource->getMigratorVersion($this->getId()) <= $this->getVersion();
		} catch (UserMigrationException) {
			return false;
		}
	}

}
