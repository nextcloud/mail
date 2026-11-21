<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Sieve;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Sieve\SieveUtils;

final class SieveUtilsTest extends TestCase {
	/**
	 * @dataProvider providerEscapeString
	 */
	public function testEscapeString(string $subject, string $expected): void {
		$actual = SieveUtils::escapeString($subject);
		$this->assertSame($expected, $actual);
	}

	public static function providerEscapeString(): \Iterator {
		yield ['foo"bar', 'foo\"bar'];
		yield ['foo\\bar', 'foo\\\\bar'];
		yield ['foo"\\bar', 'foo\"\\\\bar'];
		yield ['foobar', 'foobar'];
		yield ['', ''];
	}

	/**
	 * @dataProvider providerStringList
	 */
	public function testStringList(array $values, string $expected): void {
		$actual = SieveUtils::stringList($values);
		$this->assertSame($expected, $actual);
	}

	public static function providerStringList(): \Iterator {
		yield [['Hello', 'World'], '["Hello", "World"]'];
		yield [['foo"bar', 'foo\\bar'], '["foo\"bar", "foo\\\\bar"]'];
		yield [['foo"bar', 'foo\\bar', 'foo"\\bar'], '["foo\"bar", "foo\\\\bar", "foo\"\\\\bar"]'];
		yield [['foobar'], '["foobar"]'];
		yield [[], '[""]'];
	}
}
