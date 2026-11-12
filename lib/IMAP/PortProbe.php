<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use function fclose;
use function fsockopen;
use function is_resource;

class PortProbe {
	public function __construct(
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function canConnect(string $host, int $port): bool {
		$timeout = (float)$this->config->getSystemValue('app.mail.imap.timeout', 5);
		$socket = @fsockopen($host, $port, $errorCode, $errorMessage, $timeout);
		if (!is_resource($socket)) {
			$this->logger->debug("Discovery: cannot connect to <$host:$port>: $errorMessage");
			return false;
		}
		fclose($socket);
		return true;
	}
}
