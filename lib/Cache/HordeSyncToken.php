<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Cache;

final class HordeSyncToken {
	public function __construct(
		private readonly ?int $nextUid,
		private readonly ?int $uidValidity,
		private readonly ?int $highestModSeq,
	) {
	}

	public function getNextUid(): ?int {
		return $this->nextUid;
	}

	public function getUidValidity(): ?int {
		return $this->uidValidity;
	}

	public function getHighestModSeq(): ?int {
		return $this->highestModSeq;
	}
}
