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
use OCA\Mail\Db\CollectedAddressMapper;
use OCP\AppFramework\Db\TTransactional;
use Psr\Log\LoggerInterface;

class AddressCollector {
	use TTransactional;

	/** @var CollectedAddressMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(CollectedAddressMapper $mapper,
		LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * Add a new email addresses
	 *
	 * Duplicates are ignored
	 *
	 * @param string $userId
	 * @param AddressList $addressList
	 *
	 * @return void
	 */
	public function addAddresses(string $userId, AddressList $addressList): void {
		$this->logger->debug('collecting ' . count($addressList) . ' email addresses');
		foreach ($addressList->iterate() as $address) {
			/* @var $address Address */
			$this->saveAddress($userId, $address);
		}
	}

	/**
	 * @param string $userId
	 * @param Address $address
	 *
	 * @return void
	 */
	private function saveAddress(string $userId, Address $address): void {
		try {
			$hordeAddress = $address->toHorde();
			if (!$hordeAddress->valid) {
				throw new Horde_Mail_Exception();
			}
		} catch (Horde_Mail_Exception $ex) {
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
	 * @param string $term
	 * @return CollectedAddress[]
	 */
	public function searchAddress(string $userId, string $term): array {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($userId, $term);
		$this->logger->debug('found ' . count($result) . ' matches in collected addresses');
		return $result;
	}
}
