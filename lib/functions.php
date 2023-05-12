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

namespace OCA\Mail;

use Horde_Imap_Client_Ids;
use function array_slice;
use function array_splice;
use function max;
use function strlen;

function array_flat_map(callable $map, array $data): array {
	return array_merge([], ...array_map($map, $data));
}

/**
 * @param int[] $uids
 * @return Horde_Imap_Client_Ids[]
 */
function chunk_uid_sequence(array $uids, int $bytes): array {
	$chunks = [];
	while ($uids !== []) {
		$take = count($uids);
		while (strlen((new Horde_Imap_Client_Ids(array_slice($uids, 0, $take)))->tostring) >= $bytes) {
			$take = (int)($take * 0.75);
		}
		$chunks[] = new Horde_Imap_Client_Ids(
			array_splice($uids, 0, max($take, 1))
		);
	}
	return $chunks;
}
