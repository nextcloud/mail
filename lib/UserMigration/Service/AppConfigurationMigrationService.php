<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\PreConditionNotMetException;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AppConfigurationMigrationService {
	public const APP_CONFIGURATION_FILE = MailAccountMigrator::EXPORT_ROOT . '/app_configuration.json';

	public function __construct(
		private readonly IConfig $config,
		private readonly IL10N $l10n,
	) {
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
	public function exportAppConfiguration(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);
		$appConfigSettings = array_map(function (string $appConfigKey) use ($user) {
			return [
				'key' => $appConfigKey,
				'value' => $this->config->getUserValue($user->getUID(), Application::APP_ID, $appConfigKey)
			];
		}, $appConfigKeys);
		$exportDestination->addFileContents(self::APP_CONFIGURATION_FILE, json_encode($appConfigSettings));
	}

	/**
	 * Import the user configuration stored via IConfig
	 * on export.
	 *
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function importAppConfiguration(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		try {
			$appConfigs = json_decode($importSource->getFileContents(self::APP_CONFIGURATION_FILE), true, flags: JSON_THROW_ON_ERROR);

			foreach ($appConfigs as $appConfig) {
				$this->config->setUserValue($user->getUID(), Application::APP_ID, $appConfig['key'], $appConfig['value']);
			}
		} catch (\JsonException $e) {
		} catch (UserMigrationException $e) {
			$output->writeln($this->l10n->t("Mail app configuration for user {$user->getUID()} not found. Continue..."));
		}
	}

	/**
	 * Delete the user configuration stored via IConfig.
	 *
	 * @param IUser $user
	 * @return void
	 */
	public function deleteAppConfiguration(IUser $user): void {
		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);
		foreach ($appConfigKeys as $appConfigKey) {
			$this->config->deleteUserValue($user->getUID(), Application::APP_ID, $appConfigKey);
		}
	}

}
