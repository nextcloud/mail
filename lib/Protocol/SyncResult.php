<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Protocol;

final class SyncResult {
	/**
	 * @param int[] $new
	 * @param int[] $modified
	 * @param int[] $deleted
	 * @param array<string, mixed> $stats
	 */
	public function __construct(
		public readonly array $new = [],
		public readonly array $modified = [],
		public readonly array $deleted = [],
		public readonly ?string $state = null,
		public readonly array $stats = [],
	) {
	}
}
