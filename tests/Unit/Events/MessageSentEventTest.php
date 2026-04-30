<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Events\MessageSentEvent;

class MessageSentEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createMock(Account::class);
		$localMessage = $this->createMock(LocalMessage::class);

		$event = new MessageSentEvent($account, $localMessage);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($localMessage, $event->getLocalMessage());
	}
}
