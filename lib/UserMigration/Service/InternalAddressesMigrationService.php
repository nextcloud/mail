<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Contracts\IInternalAddressService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class InternalAddressesMigrationService {
	public const INTERNAL_ADDRESSES_FILE = MailAccountMigrator::EXPORT_ROOT . '/internal_addresses.json';

	public function __construct(
		private readonly IInternalAddressService $internalAddressService,
		private readonly IL10N $l10n,
	) {
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
	public function exportInternalAddresses(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$internalAddresses = $this->internalAddressService->getInternalAddresses($user->getUID());
		$exportDestination->addFileContents(self::INTERNAL_ADDRESSES_FILE, json_encode($internalAddresses));
	}

	/**
	 * Import all addresses the user defined as internal ones.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	public function importInternalAddresses(IUser $user, IImportSource $importSource): void {
		$internalAddresses = json_decode($importSource->getFileContents(self::INTERNAL_ADDRESSES_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($internalAddresses as $internalAddress) {
			$this->internalAddressService->add($user->getUID(), $internalAddress['address'], $internalAddress['type']);
		}
	}

	public function removeInternalAddresses(IUser $user, IImportSource $importSource): void {
		$this->internalAddressService->removeInternalAddresses($user->getUID());
	}
}
