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

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\AutoCompleteController;
use OCP\AppFramework\Http\JSONResponse;

class AutoCompleteControllerTest extends TestCase {
	private $request;
	private $service;
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->service = $this->getMockBuilder('OCA\Mail\Service\AutoCompletion\AutoCompleteService')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new AutoCompleteController(
			'mail',
			$this->request,
			$this->service,
			'testuser'
		);
	}

	public function testAutoComplete() {
		$term = 'john d';
		$result = [
			'id' => 13,
			'label' => 'johne doe',
			'value' => 'johne doe <john@doe.com>',
		];

		$this->service->expects($this->once())
			->method('findMatches')
			->with(
				'testuser',
				$this->equalTo($term)
			)
			->willReturn($result);

		$response = $this->controller->index($term);

		$this->assertEquals((new JSONResponse($result))->getData(), $response->getData());
	}
}
