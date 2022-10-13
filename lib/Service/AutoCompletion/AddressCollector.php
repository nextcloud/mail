<?php

declare(strict_types=1);

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
use Psr\Log\LoggerInterface;

class AddressCollector {
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
		$this->logger->debug("collecting " . count($addressList) . " email addresses");
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
			$this->logger->debug("<" . $address->getEmail() . "> is not a valid RFC822 mail address");
			return;
		}
		if ($address->getEmail() !== null && !$this->mapper->exists($userId, $address->getEmail())) {
			$this->logger->debug("saving new address <{$address->getEmail()}>");

			$entity = new CollectedAddress();
			$entity->setUserId($userId);
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
	 * @return CollectedAddress[]
	 */
	public function searchAddress(string $userId, string $term): array {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($userId, $term);
		$this->logger->debug("found " . count($result) . " matches in collected addresses");
		return $result;
	}
}
