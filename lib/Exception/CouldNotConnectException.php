<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
