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

namespace OCA\Mail\Tests\Unit\Service;

use OCA\Mail\Service\UserPreferenceService;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\IConfig;

class UserPreferenceServiceTest extends TestCase {
	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId = 'claire';

	/** @var UserPreferenceService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->service = new UserPreferenceService($this->config, $this->userId);
	}

	public function testGetPreference() {
		$this->config->expects($this->once())
			->method('getUserValue')
			->with($this->userId, 'mail', 'test', null)
			->willReturn('123');
		$expected = '123';

		$actual = $this->service->getPreference($this->userId, 'test');

		$this->assertEquals($expected, $actual);
	}

	public function testSetPreference() {
		$this->config->expects($this->once())
			->method('setUserValue')
			->with($this->userId, 'mail', 'test', '123')
			->willReturn('123');

		$this->service->setPreference($this->userId, 'test', '123');
	}
}
