<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\DataUri;

final class DataUri {
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
