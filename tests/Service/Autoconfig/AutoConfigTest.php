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

namespace OCA\Mail\Tests\Service\Autoconfig;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\Service\AutoConfig\ConfigurationDetector;
use OCA\Mail\Service\AutoConfig\IspDbConfigurationDetector;
use OCA\Mail\Tests\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class AutoConfigTest extends TestCase {

	/** @var IspDbConfigurationDetector|PHPUnit_Framework_MockObject_MockObject */
	private $ispDbDetector;

	/** @var ConfigurationDetector|PHPUnit_Framework_MockObject_MockObject */
	private $configDetector;

	/** @var AutoConfig */
	private $autoConfig;

	protected function setUp() {
		parent::setUp();

		$this->ispDbDetector = $this->createMock(IspDbConfigurationDetector::class);
		$this->configDetector = $this->createMock(ConfigurationDetector::class);

		$this->autoConfig = new AutoConfig($this->ispDbDetector, $this->configDetector);
	}

	public function testFailingDetection() {
		$this->ispDbDetector->expects($this->once())
			->method('detectImapAndSmtp')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn(null);
		$this->configDetector->expects($this->once())
			->method('detectImapAndSmtp')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn(null);

		$result = $this->autoConfig->createAutoDetected('user@domain.tld', 'mypassword', 'User');

		$this->assertNull($result);
	}

	public function testIspDbDetection() {
		$account = $this->createMock(MailAccount::class);
		$this->ispDbDetector->expects($this->once())
			->method('detectImapAndSmtp')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn($account);
		$this->configDetector->expects($this->never())
			->method('detectImapAndSmtp');

		$result = $this->autoConfig->createAutoDetected('user@domain.tld', 'mypassword', 'User');

		$this->assertSame($account, $result);
	}

	public function testConfigurationDetection() {
		$account = $this->createMock(MailAccount::class);
		$this->ispDbDetector->expects($this->once())
			->method('detectImapAndSmtp')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn(null);
		$this->configDetector->expects($this->once())
			->method('detectImapAndSmtp')
			->with('user@domain.tld', 'mypassword', 'User')
			->willReturn($account);

		$result = $this->autoConfig->createAutoDetected('user@domain.tld', 'mypassword', 'User');

		$this->assertSame($account, $result);
	}

}
