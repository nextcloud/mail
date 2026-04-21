<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TrustedSendersMigrationService {
	public const TRUSTED_SENDERS_FILE = MailAccountMigrator::EXPORT_ROOT . '/trusted_senders.json';

	public function __construct(
		private readonly ITrustedSenderService $trustedSenderService,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export all addresses the user defined as trustworthy.
	 *
	 * @throws UserMigrationException
	 */
	public function exportTrustedSenders(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln($this->l10n->t('Exporting trusted senders for user %s', $user->getUID()), OutputInterface::VERBOSITY_VERBOSE);

		$trustedSenders = $this->trustedSenderService->getTrusted($user->getUID());

		try {
			$exportDestination->addFileContents(self::TRUSTED_SENDERS_FILE, json_encode($trustedSenders, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException) {
			throw new UserMigrationException("Failed to export mail app configuration for user {$user->getUID()}");
		}
	}

	/**
	 * Import all addresses the user defined as trustworthy
	 * on export.
	 *
	 * @throws UserMigrationException
	 */
	public function importTrustedSenders(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Importing trusted senders for user %s', $user->getUID()),
			OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$trustedSendersFileContent = $importSource->getFileContents(self::TRUSTED_SENDERS_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Trusted senders configuration for user %s not found. Continue...', $user->getUID()),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		$trustedSenders = json_decode($trustedSendersFileContent, true);
		$this->validateTrustedSenders($trustedSenders);

		foreach ($trustedSenders as $trustedSender) {
			$output->writeln(
				$this->l10n->t('Importing trusted sender %s for user %s', [$trustedSender['email'], $user->getUID()]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			$this->trustedSenderService->trust($user->getUID(), $trustedSender['email'], $trustedSender['type']);
		}
	}

	public function removeAllTrustedSenders(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Delete existing trusted senders for user %s', $user->getUID()),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->trustedSenderService->removeTrusted($user->getUID());
	}

	/**
	 * Validate the parsed trusted senders to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateTrustedSenders(mixed $trustedSenders): void {
		$trustedSendersArrayIsValid = is_array($trustedSenders) && array_is_list($trustedSenders);
		if (!$trustedSendersArrayIsValid) {
			throw new UserMigrationException('Invalid trusted senders export structure');
		}

		foreach ($trustedSenders as $trustedSender) {
			$trustedSenderArrayIsValid = is_array($trustedSender);

			$emailIsValid = $trustedSenderArrayIsValid
				&& array_key_exists('email', $trustedSender)
				&& is_string($trustedSender['email']);

			$typeIsValid = $trustedSenderArrayIsValid
				&& array_key_exists('type', $trustedSender)
				&& is_string($trustedSender['type']);

			if (!$emailIsValid || !$typeIsValid) {
				throw new UserMigrationException('Invalid trusted sender entry');
			}
		}
	}
}
