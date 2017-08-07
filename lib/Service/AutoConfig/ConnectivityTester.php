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

namespace OCA\Mail\Service\AutoConfig;

use OCA\Mail\Service\Logger;

class ConnectivityTester {

	const CONNECTION_TIMEOUT = 5;

	/** @var Logger */
	protected $logger;

	/**
	 * @param Logger $logger
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param string $url
	 * @param integer $port
	 * @return bool
	 */
	public function canConnect($url, $port) {
		$this->logger->debug("attempting to connect to <$url> on port <$port>");
		$fp = @fsockopen($url, $port, $error, $errorstr, self::CONNECTION_TIMEOUT);
		if (is_resource($fp)) {
			fclose($fp);
			$this->logger->debug("connection to <$url> on port <$port> established");
			return true;
		}
		$this->logger->debug("cannot connect to <$url> on port <$port>");
		return false;
	}

}
