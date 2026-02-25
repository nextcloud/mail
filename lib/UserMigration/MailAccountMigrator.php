<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration;

use JsonException;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IInternalAddressService;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Service\TextBlockService;
use OCA\Mail\UserMigration\Service\AccountMigrationService;
use OCA\Mail\UserMigration\Service\AppConfigurationMigrationService;
use OCA\Mail\UserMigration\Service\InternalAddressesMigrationService;
use OCA\Mail\UserMigration\Service\QuickActionsMigrationService;
use OCA\Mail\UserMigration\Service\SMIMEMigrationService;
use OCA\Mail\UserMigration\Service\TagsMigrationService;
use OCA\Mail\UserMigration\Service\TextBlocksMigrationService;
use OCA\Mail\UserMigration\Service\TrustedSendersMigrationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\PreConditionNotMetException;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function json_decode;
use function json_encode;

class MailAccountMigrator implements IMigrator {
	use TMigratorBasicVersionHandling;

	public const EXPORT_ROOT = Application::APP_ID;
	public const FILENAME_PLACEHOLDER = '{filename}';

	public function __construct(
		private readonly AccountMigrationService           $accountMigrationService,
		private readonly AppConfigurationMigrationService  $appConfigurationMigrationService,
		private readonly InternalAddressesMigrationService $internalAddressesMigrationService,
		private readonly QuickActionsMigrationService      $quickActionsMigrationService,
		private readonly SMIMEMigrationService             $smimeMigrationService,
		private readonly TagsMigrationService              $tagsMigrationService,
		private readonly TextBlocksMigrationService              $textBlocksMigrationService,
		private readonly TrustedSendersMigrationService    $trustedSendersMigrationService,
		private readonly IL10N                             $l10n,
	) {
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @return void
	 * @throws ServiceException
	 * @throws UserMigrationException
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$this->accountMigrationService->exportAccounts($user, $exportDestination, $output);
		$this->exportAppConfiguration($user, $exportDestination, $output);
		$this->exportInternalAddresses($user, $exportDestination, $output);
		$this->exportTrustedSenders($user, $exportDestination, $output);
		$this->exportTextBlocks($user, $exportDestination, $output);
		$this->exportQuickActions($user, $exportDestination, $output);
		$this->exportTags($user, $exportDestination, $output);
		$this->exportCertificates($user, $exportDestination, $output);
	}



	/**
	 * {@inheritDoc}
	 *
	 * @param IUser $user
	 * @param IImportSource $importSource
	 * @param OutputInterface $output
	 * @throws ClientException
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws JsonException
	 * @throws PreConditionNotMetException
	 * @throws ServiceException
	 * @throws UserMigrationException
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$this->deleteExistingData($user, $output);

		$this->importAppConfiguration($user, $importSource);
		$this->importInternalAddresses($user, $importSource);
		$this->importTrustedSenders($user, $importSource);
		$this->importTextBlocks($user, $importSource);
		$tagMapping = $this->importTags($user, $importSource);
		$certificatesMapping = $this->importCertificates($user, $importSource);
		$accountAndMailboxMappings = $this->accountMigrationService->importAccounts($user, $importSource, $certificatesMapping, $output);
		$this->importQuickActions($importSource, $accountAndMailboxMappings['accounts'], $accountAndMailboxMappings['mailboxes'], $tagMapping);

		$this->accountMigrationService->scheduleBackgroundJobs($user, $output);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'mail_account';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Mail');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return $this->l10n->t('Mail app settings and manually configured accounts');
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
		$this->deleteAppConfiguration($user);
		$this->deleteAllUserCertificates($user);
		$this->accountMigrationService->deleteAllAccounts($user, $output);
	}

}
