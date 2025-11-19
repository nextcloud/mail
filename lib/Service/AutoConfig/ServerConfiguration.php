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
	public function __construct(
		private readonly string $username,
		private readonly string $host,
		private readonly int $port,
		private readonly string $security
	) {
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
