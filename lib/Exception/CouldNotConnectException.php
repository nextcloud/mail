<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Exception;

use Horde_Imap_Client_Exception;
use JmapClient\Exceptions\RequestException;
use JmapClient\Exceptions\TransportException;
use OCA\Mail\JMAP\Exception\JmapTransportException;
use OCP\AppFramework\Http;
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
		if ($this->previous instanceof RequestException) {
			return match ($this->previous->getCode()) {
				Http::STATUS_UNAUTHORIZED => 'AUTHENTICATION_WRONG_PASSWORD',
				Http::STATUS_FORBIDDEN => 'AUTHENTICATION_DENIED',
				default => 'OTHER',
			};
		}
		if ($this->previous instanceof TransportException || $this->previous instanceof JmapTransportException) {
			return 'CONNECTION_ERROR';
		}
		if (!($this->previous instanceof Horde_Imap_Client_Exception)) {
			return 'OTHER';
		}

		return match ($this->previous->getCode()) {
			Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED => match ($this->previous->getMessage()) {
				'Authentication failed.' => 'AUTHENTICATION_WRONG_PASSWORD',
				'Mail server denied authentication.' => 'AUTHENTICATION_DENIED',
				default => 'AUTHENTICATION',
			},
			Horde_Imap_Client_Exception::SERVER_CONNECT,
			Horde_Imap_Client_Exception::SERVER_READERROR => 'CONNECTION_ERROR',
			default => 'OTHER',
		};
	}
}
