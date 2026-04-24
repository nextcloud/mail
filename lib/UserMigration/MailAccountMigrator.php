<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCA\Mail\UserMigration\Service\AppConfigMigrationService;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCA\Mail\UserMigration\Service\SMIMEMigrationService;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCA\Mail\UserMigration\Service\TextBlocksMigrationService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCP\AppFramework\Db\DoesNotExistException;
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
		private readonly AppConfigMigrationService $appConfigMigrationService,
		private readonly InternalAddressesMigrationService $internalAddressesMigrationService,
		private readonly TrustedSendersMigrationService $trustedSendersMigrationService,
		private readonly TextBlocksMigrationService $textBlocksMigrationService,
		private readonly TagsMigrationService $tagsMigrationService,
		private readonly SMIMEMigrationService $smimeMigrationService,
	) {
	}

	#[\Override]
	public function export(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output,
	): void {
		$output->writeln($this->l10n->t("Exporting mail accounts for user {$user->getUID()}"), OutputInterface::VERBOSITY_VERBOSE);

		$this->appConfigMigrationService->exportAppConfiguration($user, $exportDestination, $output);
		$this->internalAddressesMigrationService->exportInternalAddresses($user, $exportDestination, $output);
		$this->trustedSendersMigrationService->exportTrustedSenders($user, $exportDestination, $output);
		$this->textBlocksMigrationService->exportTextBlocks($user, $exportDestination, $output);
		$this->tagsMigrationService->exportTags($user, $exportDestination, $output);
		$this->smimeMigrationService->exportCertificates($user, $exportDestination, $output);
	}

	#[\Override]
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln($this->l10n->t("Importing mail accounts for user {$user->getUID()}"), OutputInterface::VERBOSITY_VERBOSE);

		$this->deleteExistingData($user, $output);

		$this->appConfigMigrationService->importAppConfiguration($user, $importSource, $output);
		$this->internalAddressesMigrationService->importInternalAddresses($user, $importSource, $output);
		$this->trustedSendersMigrationService->importTrustedSenders($user, $importSource, $output);
		$this->textBlocksMigrationService->importTextBlocks($user, $importSource, $output);
		$newTagIds = $this->tagsMigrationService->importTags($user, $importSource, $output);
		$newCertificateIds = $this->smimeMigrationService->importCertificates($user, $importSource, $output);

		$this->accountMigrationService->scheduleBackgroundJobs($user, $output);
	}

	/**
	 * Delete all existing user data of our app to ensure
	 * the result of the import is always the same.
	 *
	 * @param IUser $user
	 * @param OutputInterface $output
	 * @throws ClientException
	 * @throws DoesNotExistException
	 * @throws ServiceException
	 */
	private function deleteExistingData(IUser $user, OutputInterface $output): void {
		$output->writeln($this->l10n->t("Deleting existing mail data for user {$user->getUID()}"), OutputInterface::VERBOSITY_VERBOSE);

		$this->appConfigMigrationService->deleteAppConfiguration($user, $output);
		$this->internalAddressesMigrationService->removeInternalAddresses($user, $output);
		$this->trustedSendersMigrationService->removeAllTrustedSenders($user, $output);
		$this->textBlocksMigrationService->deleteAllTextBlocks($user, $output);
		$this->tagsMigrationService->deleteAllTags($user, $output);
		$this->accountMigrationService->deleteAllAccounts($user, $output);
		$this->smimeMigrationService->deleteAllUserCertificates($user, $output);
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
