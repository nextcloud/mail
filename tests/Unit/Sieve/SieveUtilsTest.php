<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Sieve;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Sieve\SieveUtils;

class SieveUtilsTest extends TestCase {
	/**
	 * @dataProvider providerEscapeString
	 */
	public function testEscapeString(string $subject, string $expected): void {
		$actual = SieveUtils::escapeString($subject);
		$this->assertSame($expected, $actual);
	}

	public static function providerEscapeString(): array {
		return [
			['foo"bar', 'foo\"bar'],
			['foo\\bar', 'foo\\\\bar'],
			['foo"\\bar', 'foo\"\\\\bar'],
			['foobar', 'foobar'],
			['', ''],
		];
	}

	/**
	 * @dataProvider providerStringList
	 */
	public function testStringList(array $values, string $expected): void {
		$actual = SieveUtils::stringList($values);
		$this->assertSame($expected, $actual);
	}

	public static function providerStringList(): array {
		return [
			[['Hello', 'World'], '["Hello", "World"]'],
			[['foo"bar', 'foo\\bar'], '["foo\"bar", "foo\\\\bar"]'],
			[['foo"bar', 'foo\\bar', 'foo"\\bar'], '["foo\"bar", "foo\\\\bar", "foo\"\\\\bar"]'],
			[['foobar'], '["foobar"]'],
			[[], '[""]'],
		];
	}
}
