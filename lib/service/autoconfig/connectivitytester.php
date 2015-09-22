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

abstract class ConnectivityTester {

	const CONNECTION_TIMEOUT = 10;

	/**
	 * @var Logger
	 */
	protected $logger;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param string $url
	 * @param integer $port
	 * @return bool
	 */
	protected function canConnect($url, $port) {
		$this->logger->debug("attempting to connect to <$url> on port <$port>");
		$fp = fsockopen($url, $port, $error, $errorstr, self::CONNECTION_TIMEOUT);
		if (is_resource($fp)) {
			fclose($fp);
			$this->logger->debug("connection to <$url> on port <$port> established");
			return true;
		}
		$this->logger->debug("cannot connect to <$url> on port <$port>");
		return false;
	}

}
