<?php

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
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

namespace OCA\Mail\Tests\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\AutoConfig\IspDb;

class IspDbtest extends TestCase {

	private $logger;

	protected function setUp() {
		parent::setUp();

		$this->logger = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();
	}

	public function queryData() {
		return [
			['gmail.com'],
			['outlook.com'],
		];
	}

	/**
	 * @dataProvider queryData
	 *
	 * @param string $domain
	 */
	public function testQueryRealServers($domain) {
		$ispDb = new IspDb($this->logger);
		$result = $ispDb->query($domain);
		$this->assertContainsIspData($result);
	}

	public function fakeAutoconfigData() {
		return [
			['freenet.de', true],
			//['example.com', false], //should it fail?
		];
	}

	/**
	 * @dataProvider fakeAutoconfigData
	 */
	public function testQueryFakeAutoconfig($domain, $shouldSucceed) {
		$urls = [
			dirname(__FILE__) . '/../../resources/autoconfig-freenet.xml',
		];
		$ispDb = $this->getIspDbMock($urls);

		$result = $ispDb->query($domain);

		if ($shouldSucceed) {
			$this->assertContainsIspData($result);
		} else {
			$this->assertEmpty($result);
		}
	}

	private function getIspDbMock($urls) {
		$mock = $this->getMockBuilder('\OCA\Mail\Service\AutoConfig\IspDb')
			->setMethods(['getUrls'])
			->setConstructorArgs([$this->logger])
			->getMock();
		$mock->expects($this->once())
			->method('getUrls')
			->will($this->returnValue($urls));
		return $mock;
	}

	/**
	 * @todo check actual values
	 */
	private function assertContainsIspData($data) {
		$this->assertArrayHasKey('imap', $data);
		$this->assertTrue(count($data['imap']) >= 1, 'no isp imap data returned');
		$this->assertArrayHasKey('smtp', $data);
		$this->assertTrue(count($data['smtp']) >= 1, 'no isp smtp data returned');
	}

}
