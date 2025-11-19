<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Exception;

use Horde_Imap_Client_Exception;
use Throwable;

class CouldNotConnectException extends ServiceException {
	private readonly string $service;

	private readonly string $host;

	private readonly int $port;

	private readonly \Throwable $previous;

	public function __construct(Throwable $previous, string $service, string $host, int $port) {
		parent::__construct(
			"Connection to {$service} at {$host}:{$port} failed. {$previous->getMessage()}",
			$previous->getCode(),
			$previous
		);
		$this->service = $service;
		$this->host = $host;
		$this->port = $port;
		$this->previous = $previous;
	}

	public function getService(): string {
		return $this->service;
	}

	public function getHost(): string {
		return $this->host;
	}

	public function getPort(): int {
		return $this->port;
	}

	public function getReason(): string {
		if (!($this->previous instanceof Horde_Imap_Client_Exception)) {
			return 'OTHER';
		}

		return match ($this->previous->getCode()) {
			Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED => 'AUTHENTICATION',
			Horde_Imap_Client_Exception::SERVER_CONNECT, Horde_Imap_Client_Exception::SERVER_READERROR => 'CONNECTION_ERROR',
			default => 'OTHER',
		};
	}
}
