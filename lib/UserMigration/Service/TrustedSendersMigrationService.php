<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IConfig;
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
		$trustedSenders = $this->trustedSenderService->getTrusted($user->getUID());
		$exportDestination->addFileContents(self::TRUSTED_SENDERS_FILE, json_encode($trustedSenders));
	}

	/**
	 * Import all addresses the user defined as trustworthy
	 * on export.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	public function importTrustedSenders(IUser $user, IImportSource $importSource): void {
		$trustedSenders = json_decode($importSource->getFileContents(self::TRUSTED_SENDERS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($trustedSenders as $trustedSender) {
			$this->trustedSenderService->trust($user->getUID(), $trustedSender['email'], $trustedSender['type']);
		}
	}

	public function removeAllTrustedSenders(IUser $user, IImportSource $importSource): void {
		$this->trustedSenderService->removeTrusted($user->getUID());
	}
}
