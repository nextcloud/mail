<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
		$this->service = $this->getMockBuilder(\OCA\Mail\Service\AutoCompletion\AutoCompleteService::class)
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
