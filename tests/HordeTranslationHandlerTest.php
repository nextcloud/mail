<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Tests;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\HordeTranslationHandler;

class HordeTranslationHandlerTest extends TestCase {
	private $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->handler = new HordeTranslationHandler();
	}

	public function testT() {
		$message = 'Hello';

		$expected = $message;
		$actual = $this->handler->t($message);

		$this->assertEquals($expected, $actual);
	}

	public function singularPluralDataProvider() {
		return [
			[0],
			[1],
			[2],
		];
	}

	/**
	 * @dataProvider singularPluralDataProvider
	 */
	public function testNgettext($number) {
		$singular = 'mail';
		$plural = 'mails';

		$expected = $number > 1 ? $plural : $singular;
		$actual = $this->handler->ngettext($singular, $plural, $number);

		$this->assertEquals($expected, $actual);
	}
}
