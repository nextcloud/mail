<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use JsonSerializable;
use Override;

/**
 * One server of a protocol, e.g. the IMAP or the SMTP server
 */
final class ServerEndpoint implements JsonSerializable {
	public const SECURITY_SSL = 'ssl';
	public const SECURITY_STARTTLS = 'tls';
	public const SECURITY_NONE = 'none';

	/**
	 * @param self::SECURITY_* $security
	 */
	public function __construct(
		private string $username,
		private string $host,
		private int $port,
		private string $security,
	) {
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function getHost(): string {
		return $this->host;
	}

	public function getPort(): int {
		return $this->port;
	}

	/**
	 * @return self::SECURITY_*
	 */
	public function getSecurity(): string {
		return $this->security;
	}

	/**
	 * @return array{username: string, host: string, port: int, security: self::SECURITY_*}
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'username' => $this->username,
			'host' => $this->host,
			'port' => $this->port,
			'security' => $this->security,
		];
	}
}
