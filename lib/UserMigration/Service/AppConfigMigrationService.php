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

	/** JSON list of per-account settings, each entry carrying its `accountId`. */
	public const ACCOUNT_SETTINGS_KEY = 'account-settings';

	public const START_MAILBOX_ID_KEY = 'start-mailbox-id';

	public function __construct(
		private readonly IConfig $config,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Measures exactly what the export writes, so both fail on the same
	 * unencodable configuration instead of disagreeing.
	 *
	 * @throws JsonException
	 */
	public function getEstimatedExportSize(IUser $user): int {
		return strlen(json_encode($this->getAppConfigSettings($user), JSON_THROW_ON_ERROR));
	}

	/**
	 * @return list<array{key: string, value: string}>
	 */
	private function getAppConfigSettings(IUser $user): array {
		$appConfigKeys = $this->config->getUserKeys($user->getUID(), Application::APP_ID);

		return array_values(array_map(function (string $appConfigKey) use ($user) {
			return [
				'key' => $appConfigKey,
				'value' => $this->config->getUserValue($user->getUID(), Application::APP_ID, $appConfigKey)
			];
		}, $appConfigKeys));
	}

	/**
	 * Export the user configuration stored via IConfig.
	 *
	 * @throws UserMigrationException
	 */
	public function exportAppConfiguration(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t(
				'Exporting mail app configuration for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$appConfigSettings = $this->getAppConfigSettings($user);

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
	 * on export.
	 *
	 * @param array<int, int> $accountsMapping Ids of the exported accounts mapped to the imported ones
	 * @param array<int, int> $mailboxesMapping Ids of the exported mailboxes mapped to the imported ones
	 * @throws JsonException
	 */
	public function importAppConfiguration(IUser $user,
		IImportSource $importSource,
		OutputInterface $output,
		array $accountsMapping,
		array $mailboxesMapping): void {
		$output->writeln(
			$this->l10n->t(
				'Importing mail app configuration for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$appConfigFileContent = $importSource->getFileContents(self::APP_CONFIGURATION_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Mail app configuration for user %s not found. Continue...',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		try {
			$appConfig = json_decode($appConfigFileContent, true, flags: JSON_THROW_ON_ERROR);
			$this->validateAppConfig($appConfig);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Mail app configuration for user %s is invalid and will be skipped. Continue...',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		foreach ($appConfig as $appSetting) {
			$key = $appSetting['key'];
			$value = match ($key) {
				self::ACCOUNT_SETTINGS_KEY => $this->remapAccountSettings($appSetting['value'], $accountsMapping),
				self::START_MAILBOX_ID_KEY => $this->remapStartMailboxId($appSetting['value'], $mailboxesMapping),
				default => $appSetting['value'],
			};

			if ($value === null) {
				$output->writeln(
					$this->l10n->t(
						'Mail app configuration key %s for user %s could not be migrated and will be removed. Continue...',
						[$key, $user->getUID()]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				// user_migration restores every preference before the migrators
				// run, so leaving the key would keep the exporting instance's ids.
				$this->config->deleteUserValue($user->getUID(), Application::APP_ID, $key);

				continue;
			}

			$output->writeln(
				$this->l10n->t(
					'Importing mail app configuration key %s for user %s',
					[$key, $user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			/** @noinspection PhpUnhandledExceptionInspection */
			$this->config->setUserValue($user->getUID(), Application::APP_ID, $key, $value);
		}
	}

	/**
	 * Settings of accounts that were not imported are dropped.
	 *
	 * @param array<int, int> $accountsMapping
	 * @return string|null The remapped value, or null if nothing is left to import
	 * @throws JsonException
	 */
	private function remapAccountSettings(string $value, array $accountsMapping): ?string {
		try {
			$accountSettings = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return null;
		}

		if (!is_array($accountSettings) || !array_is_list($accountSettings)) {
			return null;
		}

		$remappedAccountSettings = [];

		foreach ($accountSettings as $accountSetting) {
			if (!is_array($accountSetting) || !isset($accountSetting['accountId'])
				|| !is_int($accountSetting['accountId'])
				|| !isset($accountsMapping[$accountSetting['accountId']])) {
				continue;
			}

			$accountSetting['accountId'] = $accountsMapping[$accountSetting['accountId']];
			$remappedAccountSettings[] = $accountSetting;
		}

		if ($remappedAccountSettings === []) {
			return null;
		}

		return json_encode($remappedAccountSettings, JSON_THROW_ON_ERROR);
	}

	/**
	 * @param array<int, int> $mailboxesMapping
	 * @return string|null The remapped mailbox id, or null if the mailbox was not imported
	 */
	private function remapStartMailboxId(string $value, array $mailboxesMapping): ?string {
		if (!ctype_digit($value)) {
			return null;
		}

		return isset($mailboxesMapping[(int)$value]) ? (string)$mailboxesMapping[(int)$value] : null;
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
