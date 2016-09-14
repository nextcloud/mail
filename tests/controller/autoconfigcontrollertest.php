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
use Test\TestCase;
use OCA\Mail\Controller\AutoCompleteController;

class AutoConfigControllerTest extends TestCase {

	private $request;
	private $service;
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->service = $this->getMockBuilder('OCA\Mail\Service\AutoCompletion\AutoCompleteService')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new AutoCompleteController('mail', $this->request,
			$this->service);
	}

	public function testAutoComplete() {
		$term = 'john d';
		$result = 'johne doe';

		$this->service->expects($this->once())
			->method('findMatches')
			->with($this->equalTo($term))
			->will($this->returnValue($result));

		$response = $this->controller->index($term);

		$this->assertEquals($result, $response);
	}

}
