<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Framework;

use OCA\Mail\Protocol\ConnectionPool;
use OCP\Server;
use PHPUnit\Runner\AfterTestHook;

/**
 * Pooled connections would otherwise pile up over the suite and exceed the
 * IMAP server's per-user connection limit
 */
final class ReleaseConnectionsExtension implements AfterTestHook {
	public function executeAfterTest(string $test, float $time): void {
		Server::get(ConnectionPool::class)->releaseAll();
	}
}
