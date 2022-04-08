<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\Mail\Tests\Integration\Sieve;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Sieve\SieveLogger;

class SieveLoggerTest extends TestCase {
//	public function testOpenInvalidFile(): void {
//		$this->expectException(\InvalidArgumentException::class);
//		$this->expectDeprecationMessage('Unable to use "/root/horde_sieve.log" as log file for sieve.');
//		new SieveLogger('/root/horde_sieve.log');
//	}

	public function testWriteLog(): void {
		$logFile = sys_get_temp_dir() . '/horde_sieve.log';
		@unlink($logFile);

		$logger = new SieveLogger($logFile);
		$logger->debug('Test');
		unset($logger);

		$this->assertStringEqualsFile($logFile, 'Test');
	}
}
