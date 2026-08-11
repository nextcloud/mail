<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AutoConfig;

use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ConnectivityTester {
	public function __construct(
		protected IConfig $config,
		protected LoggerInterface $logger,
	) {
	}

	/**
	 * @param string $url
	 * @param integer $port
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public function canConnect(string $url, int $port): bool {
		$this->logger->debug("attempting to connect to <$url> on port <$port>");
		$timeout = (float)$this->config->getSystemValue('app.mail.imap.timeout', 5);
		$fp = @fsockopen($url, $port, $error, $errorstr, $timeout);
		if (is_resource($fp)) {
			fclose($fp);
			$this->logger->debug("connection to <$url> on port <$port> established");
			return true;
		}
		$this->logger->debug("cannot connect to <$url> on port <$port>");
		return false;
	}
}
