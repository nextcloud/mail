<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Util;

use function base64_decode;
use function base64_encode;
use function rtrim;
use function strtr;

class Base64Url {
	/**
	 * Encodes string data into URL-safe base64 without padding (RFC 4648 §5).
	 */
	public static function encode(string $data): string {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Decodes URL-safe base64 data (RFC 4648 §5).
	 *
	 * Returns false if decoding fails.
	 */
	public static function decode(string $data): string|false {
		return base64_decode(strtr($data, '-_', '+/'), true);
	}
}
