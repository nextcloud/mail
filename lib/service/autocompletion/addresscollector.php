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
use OCA\Mail\Service\Logger;

class AddressCollector {

	private $logger;

	public function __construct(Logger $logger) {
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
	 */
	public function searchAddress($term) {
		$this->logger->debug("searching for collected address <$term>");
		return [];
	}

}
