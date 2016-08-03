<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
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
