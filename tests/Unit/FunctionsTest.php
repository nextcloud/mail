<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
