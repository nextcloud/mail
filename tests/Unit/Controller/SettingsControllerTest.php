<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
