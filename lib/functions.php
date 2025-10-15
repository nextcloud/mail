<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
