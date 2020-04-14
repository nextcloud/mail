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

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Migration\MigrateProvisioningConfig;
use OCA\Mail\Service\Provisioning\Config;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class MigrateProvisioningConfigTest extends TestCase {

	/** @var ServiceMockObject */
	private $mock;

	/** @var MigrateProvisioningConfig */
	private $repairStep;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(MigrateProvisioningConfig::class);
		$this->repairStep = $this->mock->getService();
	}


	public function testRunNoConfigToMigrate() {
		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getSystemValue')
			->with('app.mail.accounts.default')
			->willReturn('');

		$this->repairStep->run($output);
	}

	public function testRunAlreadyMigrated() {
		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getSystemValue')
			->with('app.mail.accounts.default')
			->willReturn([]);
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('getConfig')
			->willReturn($this->createMock(Config::class));

		$this->repairStep->run($output);
	}

	public function testRun() {
		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getSystemValue')
			->with('app.mail.accounts.default')
			->willReturn([]);
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('getConfig')
			->willReturn(null);
		$this->mock->getParameter('provisioningManager')
			->expects($this->once())
			->method('importConfig');
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('deleteSystemValue')
			->with('app.mail.accounts.default');

		$this->repairStep->run($output);
	}

	public function testGetName() {
		$name = $this->repairStep->getName();

		$this->assertEquals('Migrate Mail provisioning config from config.php to the database', $name);
	}
}
