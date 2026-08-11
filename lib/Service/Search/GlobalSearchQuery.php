<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Search;

class GlobalSearchQuery extends SearchQuery {
	/** @var int[] */
	private array $excludeMailboxIds = [];

	/**
	 * @return int[]
	 */
	public function getExcludeMailboxIds(): array {
		return $this->excludeMailboxIds;
	}

	/**
	 * @param int[] $mailboxIds
	 */
	public function setExcludeMailboxIds(array $mailboxIds): void {
		$this->excludeMailboxIds = $mailboxIds;
	}
}
