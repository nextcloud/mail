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
			$this->l10n->t('Exporting mail app configuration for user %s', [ $user->getUID() ]),
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
		} catch (JsonException|UserMigrationException $exception) {
			throw new UserMigrationException(
				"Failed to export mail app configuration for user {$user->getUID()}",
				previous: $exception
			);
		}
	}

	/**
	 * Import the user configuration stored via IConfig
	 * on export
	 */
	public function importAppConfiguration(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Importing mail app configuration for user %s', [ $user->getUID() ]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$appConfigFileContent = $importSource->getFileContents(self::APP_CONFIGURATION_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Mail app configuration for user %s not found. Continue...', [ $user->getUID() ]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		try {
			$appConfig = json_decode($appConfigFileContent, true, flags: JSON_THROW_ON_ERROR);
			$this->validateAppConfig($appConfig);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Mail app configuration for user %s is invalid and will be skipped. Continue...', [ $user->getUID() ]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		foreach ($appConfig as $appSetting) {
			$output->writeln(
				$this->l10n->t('Importing mail app configuration key %s for user %s', [ $appSetting['key'], $user->getUID() ]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			/** @noinspection PhpUnhandledExceptionInspection */
			$this->config->setUserValue($user->getUID(), Application::APP_ID, $appSetting['key'], $appSetting['value']);
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

			$keyIsValid = $appSettingArrayIsValid
				&& array_key_exists('key', $appSetting)
				&& is_string($appSetting['key']);

			$valueIsValid = $appSettingArrayIsValid
				&& array_key_exists('value', $appSetting)
				&& is_string($appSetting['value']);

			if (!$keyIsValid || !$valueIsValid) {
				throw new UserMigrationException('Invalid mail app configuration entry');
			}
		}
	}
}
