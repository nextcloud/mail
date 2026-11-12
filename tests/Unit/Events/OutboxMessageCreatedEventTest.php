<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\OutboxMessageCreatedEvent;

class OutboxMessageCreatedEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);
		$message = $this->createStub(Message::class);

		$event = new OutboxMessageCreatedEvent($account, $message);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($message, $event->getDraft());
	}
}
