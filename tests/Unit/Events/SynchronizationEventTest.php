<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Events\SynchronizationEvent;
use Psr\Log\LoggerInterface;

class SynchronizationEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createMock(Account::class);
		$logger = $this->createMock(LoggerInterface::class);
		$rebuildThreads = true;

		$event = new SynchronizationEvent($account, $logger, $rebuildThreads);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($logger, $event->getLogger());
		$this->assertTrue($event->isRebuildThreads());
	}

	public function testConstructorWithoutRebuildThreads(): void {
		$account = $this->createMock(Account::class);
		$logger = $this->createMock(LoggerInterface::class);
		$rebuildThreads = false;

		$event = new SynchronizationEvent($account, $logger, $rebuildThreads);

		$this->assertFalse($event->isRebuildThreads());
	}
}
