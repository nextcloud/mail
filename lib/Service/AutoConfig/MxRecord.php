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

namespace OCA\Mail\Service\AutoConfig;

use OCP\ILogger;

class MxRecord {

	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param $host
	 * @return bool|array
	 */
	public function query(string $host) {
		if (getmxrr($host, $mxRecords, $mxWeight) === false) {
			$this->logger->debug("no MX records for host <$host> found");
			return false;
		}
		$mxRecords = array_filter($mxRecords, function ($record) {
			return !empty($record);
		});
		if (empty(($mxRecords))) {
			$this->logger->debug("all records for <$host>'s MX are empty");
			return false;
		}

		$this->logger->debug("found " . count($mxRecords) . " MX records for host <$host>");

		// TODO: sort by weight
		return $mxRecords;
	}
}
