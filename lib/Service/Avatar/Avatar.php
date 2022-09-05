<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

namespace OCA\Mail\Service\Avatar;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class Avatar implements JsonSerializable {
	/** @var string */
	private $url;

	/** @var string|null */
	private $mime;

	/** @var bool */
	private $isExternal;

	/**
	 * @param string $url
	 * @param string|null $mime
	 * @param bool $isExternal
	 */
	public function __construct(string $url, string $mime = null, bool $isExternal = true) {
		$this->url = $url;
		$this->mime = $mime;
		$this->isExternal = $isExternal;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Get the MIME type of this avatar
	 *
	 * @return string|null
	 */
	public function getMime() {
		return $this->mime;
	}

	/**
	 * @return bool
	 */
	public function isExternal(): bool {
		return $this->isExternal;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'isExternal' => $this->isExternal,
			'mime' => $this->mime,
			'url' => $this->url,
		];
	}
}
