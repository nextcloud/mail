<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;

class FilterParserException extends Exception {

	public static function invalidJson(\Throwable $exception): FilterParserException {
		return new self(
			'Failed to parse filter state json: ' . $exception->getMessage(),
			0,
			$exception,
		);
	}

	public static function invalidState(): FilterParserException {
		return new self(
			'Reached an invalid state',
		);
	}
}
