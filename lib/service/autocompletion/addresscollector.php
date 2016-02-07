<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

namespace OCA\Mail\Service\AutoCompletion;

use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\CollectedAddressMapper;
use OCA\Mail\Service\Logger;

class AddressCollector {

	/** @var CollectedAddressMapper */
	private $mapper;

	/** @var Logger */
	private $logger;

	/**
	 * @param CollectedAddressMapper $mapper
	 * @param Logger $logger
	 */
	public function __construct(CollectedAddressMapper $mapper, Logger $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * Add a new email address.
	 *
	 * Duplicates aren't stored.
	 *
	 * @param Horde_Mail_Rfc822_Address $address
	 */
	public function addAddress(Horde_Mail_Rfc822_Address $address) {
		$bare = $address->bare_address;
		$this->logger->debug("collecting new address <$bare>");
	}

	/**
	 * Find and return all known and matching email addresses
	 *
	 * @param Horde_Mail_Rfc822_Address[] $term
	 * @param string $UserId
	 */
	public function searchAddress($term, $UserId) {
		$this->logger->debug("searching for collected address <$term>");
		$result = $this->mapper->findMatching($UserId, $term);
		$this->logger->debug("found " . count($result) . " matches in collected addresses");
		return $result;
	}

}
