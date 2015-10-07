<?php

namespace OCA\Mail\Service\AutoConfig;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use OCA\Mail\Service\Logger;

class MxRecord {

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @param Logger $logger
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param $host
	 * @return bool|array
	 */
	public function query($host) {
		if (getmxrr($host, $mx_records, $mx_weight) === false) {
			$this->logger->debug("no MX records for host <$host> found");
			return false;
		}
		$this->logger->debug("found " . count($mx_records) . " MX records for host <$host>");

		// TODO: sort by weight
		return $mx_records;
	}

}
