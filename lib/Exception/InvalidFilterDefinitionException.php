<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;

class InvalidFilterDefinitionException extends Exception {

	public static function invalidJson(\Throwable $exception): InvalidFilterDefinitionException {
		return new self(
			'Failed to parse filter state json: ' . $exception->getMessage(),
			0,
			$exception,
		);
	}

	public static function invalidState(): InvalidFilterDefinitionException {
		return new self(
			'Reached an invalid state',
		);
	}
}
