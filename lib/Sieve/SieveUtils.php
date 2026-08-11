<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Sieve;

class SieveUtils {
	/**
	 * Escape a string for use in a Sieve script
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc5228#section-2.4.2
	 */
	public static function escapeString(string $subject): string {
		$subject = preg_replace(
			['/\\\\/', '/"/'],
			['\\\\\\\\', '\\"'],
			$subject
		);

		return (string)$subject;
	}

	/**
	 * Return a string list for use in a Sieve script
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc5228#section-2.4.2.1
	 */
	public static function stringList(array $values): string {
		$values = array_map([self::class, 'escapeString'], $values);

		return '["' . implode('", "', $values) . '"]';
	}
}
