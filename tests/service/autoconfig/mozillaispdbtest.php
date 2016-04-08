<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

use OCA\Mail\Service\AutoConfig\MozillaIspDb;
use PHPUnit_Framework_TestCase;

class MozillaIspDbtest extends PHPUnit_Framework_TestCase {

	private $mozillaIspDb;

	protected function setUp() {
		parent::setUp();

		$logger = $this->getMockBuilder('\OCA\Mail\Service\Logger')
			->disableOriginalConstructor()
			->getMock();
		$this->mozillaIspDb = new MozillaIspDb($logger);
	}

	public function queryData() {
		return [
		    ['gmail.com'],
		    ['outlook.com'],
		    ['yahoo.de'],
		];
	}

	/**
	 * @dataProvider queryData
	 *
	 * @param string $domain
	 */
	public function testQueryGmail($domain) {
		$result = $this->mozillaIspDb->query($domain);

		$this->assertContainsIspData($result);
	}

	private function assertContainsIspData($data) {
		$this->assertArrayHasKey('imap', $data);
		$this->assertTrue(count($data['imap']) >= 1, 'no isp imap data returned');
		$this->assertArrayHasKey('smtp', $data);
		$this->assertTrue(count($data['smtp']) >= 1, 'no isp smtp data returned');
	}

}
