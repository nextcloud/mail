<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Avatar;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
final class Avatar implements JsonSerializable {
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
	public function __construct(string $url, ?string $mime = null, bool $isExternal = true) {
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

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'isExternal' => $this->isExternal,
			'mime' => $this->mime,
			'url' => $this->url,
		];
	}
}
