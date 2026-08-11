<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Events\MailboxesSynchronizedEvent;

class MailboxesSynchronizedEventTest extends TestCase {
	public function testConstructorAndGetter(): void {
		$account = $this->createStub(Account::class);

		$event = new MailboxesSynchronizedEvent($account);

		$this->assertSame($account, $event->getAccount());
	}
}
