<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\AutoConfig;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
final class ServerConfiguration implements JsonSerializable {
	private string $username;
	private string $host;
	private int $port;
	private string $security;

	public function __construct(string $username,
		string $host,
		int $port,
		string $security) {
		$this->username = $username;
		$this->host = $host;
		$this->port = $port;
		$this->security = $security;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'username' => $this->username,
			'host' => $this->host,
			'port' => $this->port,
			'security' => $this->security,
		];
	}
}
