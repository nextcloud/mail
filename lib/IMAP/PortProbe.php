<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;
use function fclose;
use function fsockopen;
use function is_resource;

class PortProbe {
	public function __construct(
		private IConfig $config,
		private IRemoteHostValidator $hostValidator,
		private LoggerInterface $logger,
	) {
	}

	public function canConnect(string $host, int $port): bool {
		$host = $this->validateHost($host);
		if ($host === null) {
			return false;
		}

		$timeout = (float)$this->config->getSystemValue('app.mail.imap.timeout', 5);
		$socket = @fsockopen($host, $port, $errorCode, $errorMessage, $timeout);
		if (!is_resource($socket)) {
			$this->logger->debug("Discovery: cannot connect to <$host:$port>: $errorMessage");
			return false;
		}
		fclose($socket);
		return true;
	}

	/**
	 * @return string|null the host if it is allowed as a remote host, null otherwise
	 *
	 * @psalm-taint-escape ssrf
	 */
	private function validateHost(string $host): ?string {
		return $this->hostValidator->isValid($host) ? $host : null;
	}
}
