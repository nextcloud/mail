<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
