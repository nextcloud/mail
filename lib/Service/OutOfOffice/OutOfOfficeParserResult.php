<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\OutOfOffice;

use JsonSerializable;
use ReturnTypeWillChange;

class OutOfOfficeParserResult implements JsonSerializable {
	public function __construct(
		private ?OutOfOfficeState $state,
		private string $sieveScript,
		private string $untouchedSieveScript,
	) {
	}

	public function getState(): ?OutOfOfficeState {
		return $this->state;
	}

	public function getSieveScript(): string {
		return $this->sieveScript;
	}

	public function getUntouchedSieveScript(): string {
		return $this->untouchedSieveScript;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'state' => $this->getState()?->jsonSerialize(),
			'script' => $this->getSieveScript(),
			'untouchedScript' => $this->getUntouchedSieveScript(),
		];
	}
}
