<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use JsonSerializable;
use ReturnTypeWillChange;

class FilterState implements JsonSerializable {
	public const DEFAULT_VERSION = 1;

	public function __construct(
		private array $filters,
	) {
	}

	public static function fromJson(array $data): self {
		return new self($data);
	}

	public function getFilters(): array {
		return $this->filters;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->filters;
	}
}
