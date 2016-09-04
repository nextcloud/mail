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

use Horde_Mail_Rfc822_Address;
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
	 * @param string[] $addresses
	 */
	public function addAddresses($addresses) {
		$this->logger->debug("collecting " . count($addresses) . " email addresses");
		foreach ($addresses as $address) {
			
			if (!$this->mapper->exists($this->userId, $address)) {
				$this->logger->debug("saving new address <$address>");
				
				$entity = new CollectedAddress();
				$entity->setUserId($this->userId);
				$entity->setEmail($address);
				$this->mapper->insert($entity);
			}
		}
	}

	/**
	 * Find and return all known and matching email addresses
	 *
	 * @param Horde_Mail_Rfc822_Address[] $term
	 */
	public function searchAddress($term) {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($this->userId, $term);
		$this->logger->debug("found " . count($result) . " matches in collected addresses");
		return $result;
	}

}
