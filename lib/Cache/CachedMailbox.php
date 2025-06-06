<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Cache;

final class CachedMailbox {
	/** @var int[]|null */
	private ?array $uids = null;

	private ?int $uidValidity = null;
	private ?int $highestModSeq = null;

	/**
	 * @return int[]|null
	 */
	public function getUids(): ?array {
		return $this->uids;
	}

	/**
	 * @param int[]|null $uids
	 */
	public function setUids(?array $uids): void {
		$this->uids = $uids;
	}

	public function getUidValidity(): ?int {
		return $this->uidValidity;
	}

	public function setUidValidity(?int $uidvalid): void {
		$this->uidValidity = $uidvalid;
	}

	public function getHighestModSeq(): ?int {
		return $this->highestModSeq;
	}

	public function setHighestModSeq(?int $highestModSeq): void {
		$this->highestModSeq = $highestModSeq;
	}
}
