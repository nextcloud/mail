<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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
namespace OCA\Mail\Service\AutoConfig;

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
