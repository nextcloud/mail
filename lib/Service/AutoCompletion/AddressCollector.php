<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AutoCompletion;

use Horde_Mail_Exception;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\CollectedAddress;
use OCP\AppFramework\Db\TTransactional;

class AddressCollector {
	use TTransactional;

	public function __construct(
		private \OCA\Mail\Db\CollectedAddressMapper $mapper,
		private \Psr\Log\LoggerInterface $logger
	) {
	}

	/**
	 * Add a new email addresses
	 *
	 * Duplicates are ignored
	 *
	 *
	 */
	public function addAddresses(string $userId, AddressList $addressList): void {
		$this->logger->debug('collecting ' . count($addressList) . ' email addresses');
		foreach ($addressList->iterate() as $address) {
			/* @var $address Address */
			$this->saveAddress($userId, $address);
		}
	}

	private function saveAddress(string $userId, Address $address): void {
		try {
			$hordeAddress = $address->toHorde();
			if (!$hordeAddress->valid) {
				throw new Horde_Mail_Exception();
			}
		} catch (Horde_Mail_Exception) {
			// Ignore it
			$this->logger->debug('<' . $address->getEmail() . '> is not a valid RFC822 mail address');
			return;
		}
		if ($address->getEmail() !== null && $this->mapper->insertIfNew($userId, $address->getEmail(), $address->getLabel())) {
			$this->logger->debug("saved new address <{$address->getEmail()}>");
		}
	}

	/**
	 * Find and return all known and matching email addresses
	 *
	 * @return CollectedAddress[]
	 */
	public function searchAddress(string $userId, string $term): array {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($userId, $term);
		$this->logger->debug('found ' . count($result) . ' matches in collected addresses');
		return $result;
	}
}
