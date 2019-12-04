<?php declare(strict_types=1);

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

namespace OCA\Mail\Tests\Unit\Service\Provisioning;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Provisioning\Config;
use OCA\Mail\Service\Provisioning\ConfigMapper;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigMapperTest extends TestCase {

	/** @var ServiceMockObject */
	private $mock;

	/** @var ConfigMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mock = $this->createServiceMock(ConfigMapper::class);
		$this->mapper = $this->mock->getService();
	}

	public function testSave() {
		/** @var Config|MockObject $config */
		$config = $this->createMock(Config::class);
		$config->expects($this->once())
			->method('jsonSerialize')
			->willReturn([]);
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('setAppValue')
			->with('mail', 'provisioning_settings', '[]');

		$this->mapper->save($config);
	}

	public function testLoadNoConfig() {
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getAppValue')
			->with('mail', 'provisioning_settings')
			->willReturn('');

		$config = $this->mapper->load();

		$this->assertNull($config);
	}

	public function testLoad() {
		$this->mock->getParameter('config')
			->expects($this->once())
			->method('getAppValue')
			->with('mail', 'provisioning_settings')
			->willReturn('[]');

		$config = $this->mapper->load();

		$this->assertInstanceOf(Config::class, $config);
	}

}
