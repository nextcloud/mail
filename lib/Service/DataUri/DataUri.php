<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\DataUri;

final class DataUri {
	public function __construct(
		private readonly string $mediaType,
		private readonly array $parameters,
		private readonly bool $base64,
		private readonly string $data
	) {
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
