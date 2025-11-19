<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use JsonSerializable;
use ReturnTypeWillChange;

final class Quota implements JsonSerializable {
	public function __construct(
		private readonly int $usage,
		private readonly int $limit
	) {
	}

	public function getUsage(): int {
		return $this->usage;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'usage' => $this->getUsage(),
			'limit' => $this->getLimit(),
		];
	}
}
