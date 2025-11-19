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
	public function __construct(
		private readonly string $url,
		private readonly ?string $mime = null,
		private readonly bool $isExternal = true,
	) {
	}

	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Get the MIME type of this avatar
	 */
	public function getMime(): ?string {
		return $this->mime;
	}

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
