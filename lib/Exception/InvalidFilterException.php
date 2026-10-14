<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use OCP\AppFramework\Http;

class InvalidFilterException extends ClientException {
	public static function invalidProperty(int $index, string $property): self {
		return new self(sprintf('Filter %d has an invalid %s', $index, $property));
	}

	#[\Override]
	public function getHttpCode(): int {
		return Http::STATUS_UNPROCESSABLE_ENTITY;
	}
}
