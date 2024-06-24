<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use OCA\Mail\Controller\SettingsController;
use OCA\Mail\Db\Provisioning;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\AppFramework\Http\JSONResponse;

class SettingsControllerTest extends TestCase {
	/** @var ServiceMockObject */
	private $mock;

	/** @var SettingsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(SettingsController::class);
		$this->controller = $this->mock->getService();
	}

	public function testProvisioning() {
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('provision')
			->with();

		$response = $this->controller->provision();

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testCreateProvisioning(): void {
		$provisioning = new Provisioning();
		$this->mock->getParameter('provisioningManager')
			->expects(self::once())
			->method('newProvisioning')
			->willReturn($provisioning);

		$response = $this->controller->createProvisioning([]);

		self::assertEquals(new JSONResponse($provisioning), $response);
	}

	public function testDeprovision() {
		$provisioning = new Provisioning();
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('getConfigById')
			->willReturn($provisioning);
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('deprovision')
			->with($provisioning);
		$response = $this->controller->deprovision(1);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}
}
