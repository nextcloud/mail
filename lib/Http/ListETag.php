<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http;

use JsonException;

/**
 * The ETag of a list that the browser keeps and asks for changes of with If-None-Match
 */
final class ListETag {
	/**
	 * @throws JsonException
	 */
	public static function of(array $list): string {
		return md5(json_encode($list, JSON_THROW_ON_ERROR));
	}

	/**
	 * @return string the ETag of $list as the browser sends it back in If-None-Match
	 * @throws JsonException
	 */
	public static function header(array $list): string {
		return '"' . self::of($list) . '"';
	}
}
