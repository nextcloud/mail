<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Cache;

class HordeSyncTokenParser {
	public function parseSyncToken(string $token): HordeSyncToken {
		$decodedToken = base64_decode($token, true);
		$parts = explode(',', $decodedToken);

		$nextUid = null;
		$uidValidity = null;
		$highestModSeq = null;
		foreach ($parts as $part) {
			if (str_starts_with($part, 'U')) {
				$nextUid = (int)substr($part, 1);
			}

			if (str_starts_with($part, 'V')) {
				$uidValidity = (int)substr($part, 1);
			}

			if (str_starts_with($part, 'H')) {
				$highestModSeq = (int)substr($part, 1);
			}
		}

		return new HordeSyncToken($nextUid, $uidValidity, $highestModSeq);
	}
}
