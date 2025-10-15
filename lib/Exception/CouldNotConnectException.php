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
	/** @var string */
	private $service;

	/** @var string */
	private $host;

	/** @var int */
	private $port;

	/** @var Throwable */
	private $previous;

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

	/**
	 * @return string
	 */
	public function getService(): string {
		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getHost(): string {
		return $this->host;
	}

	/**
	 * @return int
	 */
	public function getPort(): int {
		return $this->port;
	}

	public function getReason(): string {
		if (!($this->previous instanceof Horde_Imap_Client_Exception)) {
			return 'OTHER';
		}

		switch ($this->previous->getCode()) {
			case Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED:
				return 'AUTHENTICATION';
			case Horde_Imap_Client_Exception::SERVER_CONNECT:
			case Horde_Imap_Client_Exception::SERVER_READERROR:
				return 'CONNECTION_ERROR';
			default:
				return 'OTHER';
		}
	}
}
