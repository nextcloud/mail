<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Unit\Service\OutOfOffice;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTimeImmutable;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParser;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;

class OutOfOfficeParserTest extends TestCase {
	private OutOfOfficeParser $outOfOfficeParser;

	protected function setUp(): void {
		parent::setUp();

		$this->outOfOfficeParser = new OutOfOfficeParser();
	}

	public function testParseEnabledResponder(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on.txt");
		$cleanedScript = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(true, $actual->getState()->isEnabled());
		self::assertEquals(new DateTimeImmutable("2022-09-02"), $actual->getState()->getStart());
		self::assertEquals(new DateTimeImmutable("2022-09-08"), $actual->getState()->getEnd());
		self::assertEquals("On vacation", $actual->getState()->getSubject());
		self::assertEquals("I'm on vacation.", $actual->getState()->getMessage());
	}

	public function testParseDisabledResponder(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-off.txt");
		$cleanedScript = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(false, $actual->getState()->isEnabled());
		self::assertEquals(null, $actual->getState()->getStart());
		self::assertEquals(null, $actual->getState()->getEnd());
		self::assertEquals("On vacation", $actual->getState()->getSubject());
		self::assertEquals("I'm on vacation.", $actual->getState()->getMessage());
	}

	public function testParseLeaveForeignScriptUntouched(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($script, $actual->getUntouchedSieveScript());
		self::assertEquals(null, $actual->getState());
	}

	public function testBuildEnabledResponder(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable("2022-09-02"),
				new DateTimeImmutable("2022-09-08"),
				"On vacation",
				"I'm on vacation.",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithoutEndDate(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on-no-end-date.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable("2022-09-02"),
				null,
				"On vacation",
				"I'm on vacation.",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSpecialCharsInMessage(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on-special-chars-message.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable("2022-09-02"),
				null,
				"On vacation",
				"I'm on vacation.\n\"Hello, World!\"\n\\ escaped backslash",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSpecialCharsInSubject(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on-special-chars-subject.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable("2022-09-02"),
				null,
				"On vacation, \"Hello, World!\", \\ escaped backslash",
				"I'm on vacation.",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSubjectPlaceholder(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-on-subject-placeholder.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable("2022-09-02"),
				new DateTimeImmutable("2022-09-08"),
				'Re: ${subject}',
				"I'm on vacation.",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildDisabledResponder(): void {
		$script = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-cleaned.txt");
		$expected = file_get_contents(__DIR__ . "/../../../data/sieve-vacation-off.txt");

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				false,
				null,
				null,
				"On vacation",
				"I'm on vacation.",
			),
			$script,
			["Test Test <test@test.org>", "Test Alias <alias@test.org>"],
		);
		self::assertEquals($expected, $actual);
	}
}
