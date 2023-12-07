<?php

declare(strict_types=1);

/**
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Service\DataUri;

class DataUri {
	private string $mediaType;
	private array $parameters;
	private bool $base64;
	private string $data;

	public function __construct(string $mediaType, array $parameters, bool $base64, $data) {
		$this->mediaType = $mediaType;
		$this->parameters = $parameters;
		$this->base64 = $base64;
		$this->data = $data;
	}

	public function getMediaType(): string {
		return $this->mediaType;
	}

	public function getParameters(): array {
		return $this->parameters;
	}

	public function isBase64(): bool {
		return $this->base64;
	}

	public function getData(): string {
		return $this->data;
	}
}
