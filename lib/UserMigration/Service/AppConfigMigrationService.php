<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class AppConfigMigrationService {
	public const APP_CONFIGURATION_FILE = MailAccountMigrator::EXPORT_ROOT . '/app_configuration.json';

	public function __construct(
		private readonly IConfig $config,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export the user configuration stored via IConfig.
	 *
	 * @throws UserMigrationException
	 */
	public function exportAppConfiguration(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t("Exporting mail app configuration for user {$user->getUID()}"),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);
		$appConfigSettings = array_map(function (string $appConfigKey) use ($user) {
			return [
				'key' => $appConfigKey,
				'value' => $this->config->getUserValue($user->getUID(), Application::APP_ID, $appConfigKey)
			];
		}, $appConfigKeys);

		try {
			$exportDestination->addFileContents(self::APP_CONFIGURATION_FILE, json_encode($appConfigSettings, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException) {
			throw new UserMigrationException("Failed to export mail app configuration for user {$user->getUID()}");
		}
	}

	/**
	 * Import the user configuration stored via IConfig
	 * on export
	 *
	 * @throws \OCP\PreConditionNotMetException
	 * @throws \OCP\UserMigration\UserMigrationException
	 */
	public function importAppConfiguration(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t("Importing mail app configuration for user {$user->getUID()}"),
			OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$appConfigFileContent = $importSource->getFileContents(self::APP_CONFIGURATION_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t("Mail app configuration for user {$user->getUID()} not found. Continue..."),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		$appConfig = json_decode($appConfigFileContent, true);
		$this->validateAppConfig($appConfig);

		foreach ($appConfig as $appSetting) {
			$output->writeln(
				$this->l10n->t("Importing mail app configuration key {$appSetting['key']} for user {$user->getUID()}"),
				OutputInterface::VERBOSITY_VERBOSE
			);

			$this->config->setUserValue($user->getUID(), Application::APP_ID, $appSetting['key'], $appSetting['value']);
		}

	}

	/**
	 * Delete the user configuration stored via IConfig.
	 */
	public function deleteAppConfiguration(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t("Delete existing mail app configuration for user {$user->getUID()}"),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);

		foreach ($appConfigKeys as $appConfigKey) {
			$output->writeln(
				$this->l10n->t("Deleting mail app configuration key {$appConfigKey} for user {$user->getUID()}"),
				OutputInterface::VERBOSITY_VERBOSE
			);

			$this->config->deleteUserValue($user->getUID(), Application::APP_ID, $appConfigKey);
		}
	}

	/**
	 * Validate the parsed app configuration and their containing
	 * settings to ensure they have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateAppConfig(mixed $appConfig): void {
		$appConfigArrayIsValid = is_array($appConfig) && array_is_list($appConfig);
		if (!$appConfigArrayIsValid) {
			throw new UserMigrationException('Invalid mail app configuration export structure');
		}

		foreach ($appConfig as $appSetting) {
			$appSettingArrayIsValid = is_array($appSetting);
			$keyIsValid = array_key_exists('key', $appSetting) && is_string($appSetting['key']);
			$valueIsValid = array_key_exists('value', $appSetting) && is_string($appSetting['value']);

			if (!$appSettingArrayIsValid || !$keyIsValid || !$valueIsValid) {
				throw new UserMigrationException('Invalid mail app configuration entry');
			}
		}
	}
}
