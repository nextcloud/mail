<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\AutoCompletion;

use Horde_Mail_Exception;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCA\Mail\Service\Logger;

class AddressCollector {

	/** @var CollectedAddressMapper */
	private $mapper;

	/** @var string */
	private $userId;

	/** @var Logger */
	private $logger;

	/**
	 * @param CollectedAddressMapper $mapper
	 * @param string $UserId
	 * @param Logger $logger
	 */
	public function __construct(CollectedAddressMapper $mapper, $UserId, Logger $logger) {
		$this->mapper = $mapper;
		$this->userId = $UserId;
		$this->logger = $logger;
	}

	/**
	 * Add a new email addresses
	 *
	 * Duplicates are ignored
	 *
	 * @param AddressList $addressList
	 */
	public function addAddresses(AddressList $addressList) {
		$this->logger->debug("collecting " . count($addressList) . " email addresses");
		foreach ($addressList->iterate() as $address) {
			/* @var $address Address */
			$this->saveAddress($address);
		}
	}

	/**
	 * @param Address $address
	 */
	private function saveAddress(Address $address) {
		try {
			$hordeAddress = $address->toHorde();
			if (!$hordeAddress->valid) {
				throw new Horde_Mail_Exception();
			}
		} catch (Horde_Mail_Exception $ex) {
			// Ignore it
			$this->logger->debug("<$address> is not a valid RFC822 mail address");
			return;
		}
		if (!$this->mapper->exists($this->userId, $address->getEmail())) {
			$this->logger->debug("saving new address <{$address->getEmail()}>");

			$entity = new CollectedAddress();
			$entity->setUserId($this->userId);
			if ($address->getLabel() !== $address->getEmail()) {
				$entity->setDisplayName($address->getLabel());
			}
			$entity->setEmail($address->getEmail());
			$this->mapper->insert($entity);
		}
	}

	/**
	 * Find and return all known and matching email addresses
	 *
	 * @param string $term
	 */
	public function searchAddress($term) {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($this->userId, $term);
		$this->logger->debug("found " . count($result) . " matches in collected addresses");
		return $result;
	}

}
