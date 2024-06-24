<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Ids;
use function OCA\Mail\array_flat_map;
use function OCA\Mail\chunk_uid_sequence;

class FunctionsTest extends TestCase {
	public function testFlatMapEmpty(): void {
		$map = function () {
		};

		$result = array_flat_map($map, []);

		self::assertEmpty($result);
	}

	public function testFlatMap(): void {
		$double = function ($x) {
			return [$x, $x];
		};
		$data = [
			1,
			2,
			3,
		];

		$result = array_flat_map($double, $data);

		self::assertEquals(
			[
				1,
				1,
				2,
				2,
				3,
				3,
			],
			$result
		);
	}

	public function testChunkEmptyUidRange(): void {
		$uids = [];

		$chunks = chunk_uid_sequence($uids, 100);

		self::assertEquals([], $chunks);
	}

	public function testChunkShortByteRange(): void {
		$uids = [100, 101]; // 100:101 -> 7 chars

		$chunks = chunk_uid_sequence($uids, 10);

		self::assertEquals([
			new Horde_Imap_Client_Ids('100:101'),
		], $chunks);
	}

	public function testChunkLongByteRange(): void {
		$uids = [100, 101, 103, 105, 106, 201, 203, 204]; // 100:101, 103, 105:106, 201, 203:2.3 -> 35 chars

		$chunks = chunk_uid_sequence($uids, 10);

		self::assertEquals([
			new Horde_Imap_Client_Ids('100:101'),
			new Horde_Imap_Client_Ids('103, 105'),
			new Horde_Imap_Client_Ids('106, 201'),
			new Horde_Imap_Client_Ids('203:204'),
		], $chunks);
	}
}
