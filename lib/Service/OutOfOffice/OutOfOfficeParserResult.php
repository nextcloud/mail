<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'state' => $this->getState()?->jsonSerialize(),
			'script' => $this->getSieveScript(),
			'untouchedScript' => $this->getUntouchedSieveScript(),
		];
	}
}
