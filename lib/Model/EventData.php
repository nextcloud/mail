<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use ReturnTypeWillChange;

final class EventData implements JsonSerializable {

	public function __construct(
		private string $summary,
		private string $description,
	) {
	}

	public function getSummary(): string {
		return $this->summary;
	}

	public function getDescription(): string {
		return $this->description;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'summary' => $this->summary,
			'description' => $this->description,
		];
	}
}
