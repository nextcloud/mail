<?php

namespace OCA\Mail\Tests;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use PHPUnit_Framework_TestCase;
use OCA\Mail\HordeTranslationHandler;

class HordeTranslationHandlerTest extends PHPUnit_Framework_TestCase {

	private $handler;

	protected function setUp() {
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
