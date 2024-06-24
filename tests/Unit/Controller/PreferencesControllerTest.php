<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Controller\PreferencesController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;

class PreferencesControllerTest extends TestCase {
	/** @var IUserPreferences|PHPUnit_Framework_MockObject_MockObject */
	private $preferences;

	/** @var PreferencesController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->preferences = $this->createMock(IUserPreferences::class);

		$this->controller = new PreferencesController($request, $this->preferences, 'george');
	}

	public function testGetPreference() {
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('george', 'test')
			->willReturn(123);
		$expected = new JSONResponse(['value' => 123]);

		$actual = $this->controller->show('test');

		$this->assertEquals($expected, $actual);
	}

	public function testSetPreference() {
		$this->preferences->expects($this->once())
			->method('setPreference')
			->with('george', 'test')
			->willReturnArgument(2);
		$expected = new JSONResponse([
			'value' => 123,
		]);

		$actual = $this->controller->update('test', 123);

		$this->assertEquals($expected, $actual);
	}
}
