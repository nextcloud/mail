<?php

/**
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
use OCA\Mail\Service\AutoConfig\MxRecord;
use OCA\Mail\Service\Logger;

class MxRecordTest extends TestCase {

	/** @var MxRecord */
	private $record;

	protected function setUp() {
		parent::setUp();

		$logger = $this->createMock(Logger::class);
		$this->record = new MxRecord($logger);
	}

	public function testQuery() {
		$records = $this->record->query('nextcloud.com');

		$this->assertInternalType('array', $records);
		$this->assertNotEmpty($records);
	}

	public function testQueryNoRecord() {
		$records = $this->record->query('example.com');

		$this->assertFalse($records);
	}

}
