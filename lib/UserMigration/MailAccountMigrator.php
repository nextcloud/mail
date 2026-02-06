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
	private const INTERNAL_ADDRESSES_FILE = self::EXPORT_ROOT . '/internal_addresses.json';
	private const TRUSTED_SENDERS_FILE = self::EXPORT_ROOT . '/trusted_senders.json';
	private const TEXT_BLOCKS_FILE = self::EXPORT_ROOT . '/text_blocks.json';
	private const QUICK_ACTIONS_FILE = self::EXPORT_ROOT . '/quick_actions.json';
	private const TAGS_FILE = self::EXPORT_ROOT . '/tags.json';
	private const APP_CONFIGURATION = self::EXPORT_ROOT . '/app_configuration.json';
	private const SMIME_CERTIFICATE_FILES = self::EXPORT_ROOT . '/certificates/' . self::FILENAME_PLACEHOLDER . '.json';

	public function __construct(
		private readonly AccountMigrationService $accountMigrationService,
		private readonly IInternalAddressService $internalAddressService,
		private readonly ITrustedSenderService $trustedSenderService,
		private readonly TextBlockService $textBlockService,
		private readonly QuickActionsService $quickActionsService,
		private readonly ActionStepMapper $actionStepMapper,
		private readonly TagMapper $tagMapper,
		private readonly IConfig $config,
		private readonly SmimeService $smimeService,
		private readonly IL10N $l10n,
		private readonly ICrypto $crypto,
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
	 * Export the user configuration stored via IConfig.
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @return void
	 * @throws UserMigrationException
	 */
	private function exportAppConfiguration(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);
		$appConfigSettings = array_map(function (string $appConfigKey) use ($user) {
			return [
				'key' => $appConfigKey,
				'value' => $this->config->getUserValue($user->getUID(), Application::APP_ID, $appConfigKey)
			];
		}, $appConfigKeys);
		$exportDestination->addFileContents(self::APP_CONFIGURATION, json_encode($appConfigSettings));
	}

	/**
	 * Export all addresses the user defined as internal ones
	 * on export.
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @throws UserMigrationException
	 */
	private function exportInternalAddresses(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$internalAddresses = $this->internalAddressService->getInternalAddresses($user->getUID());
		$exportDestination->addFileContents(self::INTERNAL_ADDRESSES_FILE, json_encode($internalAddresses));
	}

	/**
	 * Export all addresses the user defined as trustworthy.
	 *
	 * @throws UserMigrationException
	 */
	private function exportTrustedSenders(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$trustedSenders = $this->trustedSenderService->getTrusted($user->getUID());
		$exportDestination->addFileContents(self::TRUSTED_SENDERS_FILE, json_encode($trustedSenders));
	}

	/**
	 * Export all text blocks the user created itself.
	 * This does not include those shared with others.
	 *
	 * @throws UserMigrationException
	 */
	private function exportTextBlocks(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$textBlocks = $this->textBlockService->findAll($user->getUID());
		$exportDestination->addFileContents(self::TEXT_BLOCKS_FILE, json_encode($textBlocks));
	}

	/**
	 * Export all quick actions the user defined across
	 * their accounts.
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @throws UserMigrationException
	 */
	private function exportQuickActions(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$quickActions = $this->quickActionsService->findAll($user->getUID());
		$exportDestination->addFileContents(self::QUICK_ACTIONS_FILE, json_encode($quickActions));
	}

	/**
	 * Export all tags the user currently uses.
	 *
	 * @throws UserMigrationException
	 */
	private function exportTags(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$tags = $this->tagMapper->getAllTagsForUser($user->getUID());
		$exportDestination->addFileContents(self::TAGS_FILE, json_encode($tags));
	}

	/**
	 * Export all S/MIME certificates added by the user
	 * on export.
	 *
	 * @throws UserMigrationException
	 * @throws ServiceException
	 * @throws \Exception
	 */
	private function exportCertificates(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$certificates = $this->smimeService->findAllCertificates($user->getUID());
		$mimeCerts = [];

		foreach ($certificates as $mimeCert) {
			$mimeCerts[$mimeCert->getId()] = [
				'id' => $mimeCert->getId(),
				'certificate' => $this->crypto->decrypt($mimeCert->getCertificate()),
				'privateKey' => $mimeCert->getPrivateKey() !== null ? $this->crypto->decrypt($mimeCert->getPrivateKey()) : null,
			];

			$exportDestination->addFileContents(str_replace(self::FILENAME_PLACEHOLDER, (string)$mimeCert->getId(), self::SMIME_CERTIFICATE_FILES), json_encode($mimeCerts[$mimeCert->getId()]));
		}

		$exportDestination->addFileContents(str_replace(self::FILENAME_PLACEHOLDER, 'index', self::SMIME_CERTIFICATE_FILES), json_encode($mimeCerts));
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
		$this->canImport($importSource);
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
		$this->internalAddressService->removeInternalAddresses($user->getUID());
		$this->trustedSenderService->removeTrusted($user->getUID());
		$this->textBlockService->deleteAll($user->getUID());
		$this->tagMapper->deleteAll($user->getUID());
		$this->deleteAllUserCertificates($user);
		$this->accountMigrationService->deleteAllAccounts($user, $output);
		$this->quickActionsService->deleteAll($user->getUID());
	}

	/**
	 * Delete the user configuration stored via IConfig.
	 *
	 * @param IUser $user
	 * @return void
	 */
	private function deleteAppConfiguration(IUser $user): void {
		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);
		foreach ($appConfigKeys as $appConfigKey) {
			$this->config->deleteUserValue($user->getUID(), Application::APP_ID, $appConfigKey);
		}
	}

	/**
	 * Delete all S/MIME certificates added by the user.
	 *
	 * @param IUser $user
	 * @return void
	 * @throws DoesNotExistException
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	private function deleteAllUserCertificates(IUser $user): void {
		$allCertificates = $this->smimeService->findAllCertificates($user->getUID());
		foreach ($allCertificates as $cert) {
			$this->smimeService->deleteCertificate($cert->getId(), $user->getUID());
		}
	}

	/**
	 * Import the user configuration stored via IConfig
	 * on export.
	 *
	 * @throws UserMigrationException
	 * @throws PreConditionNotMetException
	 * @throws JsonException
	 */
	private function importAppConfiguration(IUser $user, IImportSource $importSource): void {
		$appConfigs = json_decode($importSource->getFileContents(self::APP_CONFIGURATION), true, flags: JSON_THROW_ON_ERROR);

		foreach ($appConfigs as $appConfig) {
			$this->config->setUserValue($user->getUID(), Application::APP_ID, $appConfig['key'], $appConfig['value']);
		}
	}

	/**
	 * Import all addresses the user defined as internal ones.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	private function importInternalAddresses(IUser $user, IImportSource $importSource): void {
		$internalAddresses = json_decode($importSource->getFileContents(self::INTERNAL_ADDRESSES_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($internalAddresses as $internalAddress) {
			$this->internalAddressService->add($user->getUID(), $internalAddress['address'], $internalAddress['type']);
		}
	}

	/**
	 * Import all addresses the user defined as trustworthy
	 * on export.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	private function importTrustedSenders(IUser $user, IImportSource $importSource): void {
		$trustedSenders = json_decode($importSource->getFileContents(self::TRUSTED_SENDERS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($trustedSenders as $trustedSender) {
			$this->trustedSenderService->trust($user->getUID(), $trustedSender['email'], $trustedSender['type']);
		}
	}

	/**
	 * Import all text blocks the user created itself on export.
	 * This does not include those shared with others.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	private function importTextBlocks(IUser $user, IImportSource $importSource): void {
		$textBlocks = json_decode($importSource->getFileContents(self::TEXT_BLOCKS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($textBlocks as $textBlock) {
			$this->textBlockService->create($user->getUID(), $textBlock['title'], $textBlock['content']);
		}
	}

	/**
	 * Import all tags the user used on export.
	 *
	 * @param IUser $user
	 * @param IImportSource $importSource
	 * @return array
	 * @throws Exception
	 * @throws JsonException
	 * @throws UserMigrationException
	 */
	private function importTags(IUser $user, IImportSource $importSource): array {
		$tags = json_decode($importSource->getFileContents(self::TAGS_FILE), true, flags: JSON_THROW_ON_ERROR);
		$newTags = [];

		foreach ($tags as $tag) {
			$newTag = new Tag();

			$newTag->setUserId($user->getUID());
			$newTag->setDisplayName($tag['displayName']);
			$newTag->setImapLabel($tag['imapLabel']);
			$newTag->setColor($tag['color']);
			$newTag->setIsDefaultTag($tag['isDefaultTag']);

			$newTags[$tag['id']] = $this->tagMapper->insert($newTag)->getId();
		}

		return $newTags;
	}

	/**
	 * Import all S/MIME certificates added by the user.
	 *
	 * @return array Contains the old certificate ID as array key and the new
	 *               certificate ID as value.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 * @throws ServiceException
	 */
	private function importCertificates(IUser $user, IImportSource $importSource): array {
		$certificates = json_decode($importSource->getFileContents(str_replace(self::FILENAME_PLACEHOLDER, 'index', self::SMIME_CERTIFICATE_FILES)), true, flags: JSON_THROW_ON_ERROR);
		$certificatesMapping = [];

		foreach ($certificates as $certificateFilePath) {
			$certificate = json_decode($importSource->getFileContents($certificateFilePath), true, flags: JSON_THROW_ON_ERROR);
			$newCertificate = $this->smimeService->createCertificate($user->getUID(), $certificate['certificate'], $certificate['privateKey']);

			$oldCertificateId = $certificate['id'];
			$certificatesMapping[$oldCertificateId] = $newCertificate->getId();
		}

		return $certificatesMapping;
	}

	/**
	 * Import all quick actions the user defined across
	 * their accounts.
	 *
	 * @throws UserMigrationException
	 * @throws Exception
	 * @throws JsonException
	 */
	private function importQuickActions(IImportSource $importSource, array $accountMapping, array $mailboxMapping, array $tagMapping): void {
		$quickActions = json_decode($importSource->getFileContents(self::QUICK_ACTIONS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($quickActions as $quickAction) {
			$action = $this->quickActionsService->create($quickAction['name'], $accountMapping[$quickAction['accountId']]);
			$action->setIcon($quickAction['icon']);
			$actionSteps = array_map(function ($step) use ($action, $mailboxMapping, $tagMapping) {
				$actionStep = new ActionStep();

				$actionStep->setName($step['name']);
				$actionStep->setOrder($step['order']);
				$actionStep->setActionId($action->getId());
				$actionStep->setMailboxId($mailboxMapping[$step['mailboxId']]);
				$actionStep->setTagId($tagMapping[$step['tagId']]);

				return $this->actionStepMapper->insert($actionStep);
			}, $quickAction['actionSteps']);
			$action->setActionSteps($actionSteps);
		}
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

}
