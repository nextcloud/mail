<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\AutoConfig;

class ConnectivityTester {
	public const CONNECTION_TIMEOUT = 5;

	public function __construct(
		protected \Psr\Log\LoggerInterface $logger
	) {
	}

	public function canConnect(string $url, int $port): bool {
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
