<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit;

use ChristophWurst\Nextcloud\Testing\TestCase;
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
		$double = (fn ($x) => [$x, $x]);
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

		self::assertCount(1, $chunks);
		self::assertEquals('100:101', $chunks[0]->tostring);
	}

	public function testChunkLongByteRange(): void {
		$uids = [100, 101, 103, 105, 106, 201, 203, 204]; // 100:101, 103, 105:106, 201, 203:2.3 -> 35 chars

		$chunks = chunk_uid_sequence($uids, 10);

		self::assertCount(4, $chunks);
		self::assertEquals('100:101', $chunks[0]->tostring);
		self::assertEquals('103,105', $chunks[1]->tostring);
		self::assertEquals('106,201', $chunks[2]->tostring);
		self::assertEquals('203:204', $chunks[3]->tostring);
	}
}
