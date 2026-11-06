<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http;

use JsonException;

final class ListETag {
	/**
	 * @throws JsonException
	 */
	public static function of(array $list): string {
		return md5(json_encode($list, JSON_THROW_ON_ERROR));
	}

	/**
	 * @return string the quoted ETag, as the browser sends it in If-None-Match
	 * @throws JsonException
	 */
	public static function header(array $list): string {
		return '"' . self::of($list) . '"';
	}
}
