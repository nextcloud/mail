<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Controller;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Controller\PreferencesController;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Tests\TestCase;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;

class PreferencesControllerTest extends TestCase {

	/** @var IUserPreferences|PHPUnit_Framework_MockObject_MockObject */
	private $preferences;

	/** @var PreferencesController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->preferences = $this->createMock(IUserPreferences::class);

		$this->controller = new PreferencesController($request, $this->preferences);
	}

	public function testGetPreference() {
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('test')
			->willReturn(123);
		$expected = new JSONResponse(['value' => 123]);

		$actual = $this->controller->show('test');

		$this->assertEquals($expected, $actual);
	}

	public function testSetPreference() {
		$this->preferences->expects($this->once())
			->method('setPreference')
			->with('test');
		$expected = new JSONResponse();

		$actual = $this->controller->update('test', 123);

		$this->assertEquals($expected, $actual);
	}

}
