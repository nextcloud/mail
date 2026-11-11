<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service\OutOfOffice;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTimeImmutable;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParser;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;

class OutOfOfficeParserTest extends TestCase {
	private OutOfOfficeParser $outOfOfficeParser;

	private function extractSieveScriptMimeBoundary(string $sieveScript): string {
		if (!preg_match('/:mime (.*?)\r?\n/', $sieveScript, $matches)) {
			throw new InvalidArgumentException('MIME boundary not found');
		}
		if (!preg_match('/boundary=\\\"(.*?)\\\"/', $matches[1], $matches)) {
			throw new InvalidArgumentException('MIME boundary not found');
		}

		$boundary = $matches[1];
		return $boundary;
	}

	private function normalizeSieveScriptMimeBoundary(
		string $sieveScript,
		string $boundary
	): string {
		$sieveScript = preg_replace(
			preg_quote('/boundary=\\"' . $boundary . '\\"/'),
			'boundary=\\"<BOUNDARY>\\"',
			$sieveScript
		);

		return preg_replace(
			'/^--' . preg_quote($boundary) . '(--)?\r?$/m',
			'--<BOUNDARY>',
			$sieveScript
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->outOfOfficeParser = new OutOfOfficeParser();
	}

	public function testParseEnabledResponder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on.sieve');
		$cleanedScript = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(true, $actual->getState()->isEnabled());
		self::assertEquals(new DateTimeImmutable('2022-09-02T00:00:00+0100'), $actual->getState()->getStart());
		self::assertEquals(new DateTimeImmutable('2022-09-08T23:59:00+0100'), $actual->getState()->getEnd());
		self::assertEquals('On vacation', $actual->getState()->getSubject());
		self::assertEquals("I'm on vacation.", $actual->getState()->getMessage());
		self::assertEquals(false, $actual->getState()->isMessageHtml());
	}

	public function testParseDisabledResponder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-off.sieve');
		$cleanedScript = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(false, $actual->getState()->isEnabled());
		self::assertEquals(null, $actual->getState()->getStart());
		self::assertEquals(null, $actual->getState()->getEnd());
		self::assertEquals('On vacation', $actual->getState()->getSubject());
		self::assertEquals("I'm on vacation.", $actual->getState()->getMessage());
		self::assertEquals(false, $actual->getState()->isMessageHtml());
	}

	public function testParseLeaveForeignScriptUntouched(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($script, $actual->getUntouchedSieveScript());
		self::assertEquals(null, $actual->getState());
	}

	public function testParseOldEnabledResponder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-no-tz.sieve');
		$cleanedScript = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(true, $actual->getState()->isEnabled());
		self::assertEquals(new DateTimeImmutable('2022-09-02T00:00:00+0000'), $actual->getState()->getStart());
		self::assertEquals(new DateTimeImmutable('2022-09-08T00:00:00+0000'), $actual->getState()->getEnd());
		self::assertEquals('On vacation', $actual->getState()->getSubject());
		self::assertEquals("I'm on vacation.", $actual->getState()->getMessage());
		self::assertEquals(false, $actual->getState()->isMessageHtml());
	}

	public function testBuildEnabledResponder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				new DateTimeImmutable('2022-09-08T23:59:00+0100'),
				'On vacation',
				"I'm on vacation.",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithoutEndDate(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-no-end-date.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				null,
				'On vacation',
				"I'm on vacation.",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSpecialCharsInMessage(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-special-chars-message.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				null,
				'On vacation',
				"I'm on vacation.\r\n\"Hello, World!\"\r\n\\ escaped backslash",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSpecialCharsInSubject(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-special-chars-subject.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				null,
				'On vacation, "Hello, World!", \\ escaped backslash',
				"I'm on vacation.",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSubjectPlaceholder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-subject-placeholder.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				new DateTimeImmutable('2022-09-08T23:59:00+0100'),
				'Re: ${subject}',
				"I'm on vacation.",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testBuildDisabledResponder(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-off.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				false,
				null,
				null,
				'On vacation',
				"I'm on vacation.",
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);
		self::assertEquals($expected, $actual);
	}

	public function testParseEnabledResponderHtml(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-html.sieve');
		$cleanedScript = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');

		$actual = $this->outOfOfficeParser->parseOutOfOfficeState($script);
		self::assertEquals($script, $actual->getSieveScript());
		self::assertEquals($cleanedScript, $actual->getUntouchedSieveScript());
		self::assertEquals(1, $actual->getState()->getVersion());
		self::assertEquals(true, $actual->getState()->isEnabled());
		self::assertEquals(new DateTimeImmutable('2022-09-02T00:00:00+0100'), $actual->getState()->getStart());
		self::assertEquals(new DateTimeImmutable('2022-09-08T23:59:00+0100'), $actual->getState()->getEnd());
		self::assertEquals('On vacation', $actual->getState()->getSubject());
		self::assertEquals("<p style=\"margin:0;\">I'm on vacation.</p>", $actual->getState()->getMessage());
		self::assertEquals(true, $actual->getState()->isMessageHtml());
	}

	public function testBuildEnabledResponderHtml(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-html.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				new DateTimeImmutable('2022-09-08T23:59:00+0100'),
				'On vacation',
				"<p style=\"margin:0;\">I'm on vacation.</p>",
				OutOfOfficeState::DEFAULT_VERSION,
				true
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);

		$expected = self::normalizeSieveScriptMimeBoundary(
			$expected,
			self::extractSieveScriptMimeBoundary($expected)
		);
		$actual = self::normalizeSieveScriptMimeBoundary(
			$actual,
			self::extractSieveScriptMimeBoundary($actual)
		);

		self::assertEquals($expected, $actual);
	}

	public function testBuildEnabledResponderWithSubjectPlaceholderHtml(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-on-subject-placeholder-html.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				true,
				new DateTimeImmutable('2022-09-02T00:00:00+0100'),
				new DateTimeImmutable('2022-09-08T23:59:00+0100'),
				'Re: ${subject}',
				"<p style=\"margin:0;\">I'm on vacation.</p>",
				OutOfOfficeState::DEFAULT_VERSION,
				true
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);

		$expected = self::normalizeSieveScriptMimeBoundary(
			$expected,
			self::extractSieveScriptMimeBoundary($expected)
		);
		$actual = self::normalizeSieveScriptMimeBoundary(
			$actual,
			self::extractSieveScriptMimeBoundary($actual)
		);

		self::assertEquals($expected, $actual);
	}

	public function testBuildDisabledResponderHtml(): void {
		$script = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-cleaned.sieve');
		$expected = file_get_contents(__DIR__ . '/../../../data/sieve-vacation-off-html.sieve');

		$actual = $this->outOfOfficeParser->buildSieveScript(
			new OutOfOfficeState(
				false,
				null,
				null,
				'On vacation',
				"<p style=\"margin:0;\">I'm on vacation.</p>",
				OutOfOfficeState::DEFAULT_VERSION,
				true
			),
			$script,
			['Test Test <test@test.org>', 'Test Alias <alias@test.org>'],
		);

		self::assertEquals($expected, $actual);
	}
}
