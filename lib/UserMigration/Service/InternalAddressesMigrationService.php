<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Contracts\IInternalAddressService;
use OCA\Mail\UserMigration\MailAccountMigrator;
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
		$output->writeln(
			$this->l10n->t('Exporting internal addresses for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$internalAddresses = $this->internalAddressService->getInternalAddresses($user->getUID());

		try {
			$exportDestination->addFileContents(self::INTERNAL_ADDRESSES_FILE, json_encode($internalAddresses, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException $exception) {
			throw new UserMigrationException(
				"Failed to export internal addresses for user {$user->getUID()}",
				previous: $exception
			);
		}
	}

	/**
	 * Import all addresses the user defined as internal ones.
	 *
	 * @throws UserMigrationException
	 */
	public function importInternalAddresses(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Importing internal addresses for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$internalAddresses = json_decode($importSource->getFileContents(self::INTERNAL_ADDRESSES_FILE), true);
		$this->validateInternalAddresses($internalAddresses);

		foreach ($internalAddresses as $internalAddress) {
			$this->internalAddressService->add($user->getUID(), $internalAddress['address'], $internalAddress['type']);
		}
	}

	public function removeInternalAddresses(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Deleting all internal addresses for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->internalAddressService->removeInternalAddresses($user->getUID());
	}

	/**
	 * Validate the parsed internal addresses to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateInternalAddresses(mixed $internalAddresses): void {
		$internalAddressesArrayIsValid = is_array($internalAddresses) && array_is_list($internalAddresses);
		if (!$internalAddressesArrayIsValid) {
			throw new UserMigrationException('Invalid internal addresses export structure');
		}

		foreach ($internalAddresses as $internalAddress) {
			$internalAddressArrayIsValid = is_array($internalAddress);

			$idIsValid = $internalAddressArrayIsValid
				&& array_key_exists('id', $internalAddress)
				&& is_int($internalAddress['id']);

			$addressIsValid = $internalAddressArrayIsValid
				&& array_key_exists('address', $internalAddress)
				&& is_string($internalAddress['address']);

			$uidIsValid = $internalAddressArrayIsValid
				&& array_key_exists('uid', $internalAddress)
				&& is_string($internalAddress['uid']);

			$typeIsValid = $internalAddressArrayIsValid
				&& array_key_exists('type', $internalAddress)
				&& is_string($internalAddress['type']);

			if (
				!$idIsValid
				|| !$addressIsValid
				|| !$uidIsValid
				|| !$typeIsValid
			) {
				throw new UserMigrationException('Invalid internal address entry');
			}
		}
	}
}
