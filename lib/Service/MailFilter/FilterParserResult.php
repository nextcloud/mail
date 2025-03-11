<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\MailFilter;

use JsonSerializable;
use ReturnTypeWillChange;

class FilterParserResult implements JsonSerializable {
	public function __construct(
		private array $filters,
		private string $sieveScript,
		private string $untouchedSieveScript,
	) {
	}

	public function getFilters(): array {
		return $this->filters;
	}

	public function getSieveScript(): string {
		return $this->sieveScript;
	}

	public function getUntouchedSieveScript(): string {
		return $this->untouchedSieveScript;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'filters' => $this->filters,
			'script' => $this->getSieveScript(),
			'untouchedScript' => $this->getUntouchedSieveScript(),
		];
	}
}
