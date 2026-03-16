<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Events\NewMessagesSynchronized;

class NewMessagesSynchronizedTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$messages = [
			$this->createMock(Message::class),
			$this->createMock(Message::class),
		];

		$event = new NewMessagesSynchronized($account, $mailbox, $messages);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($mailbox, $event->getMailbox());
		$this->assertSame($messages, $event->getMessages());
		$this->assertCount(2, $event->getMessages());
	}

	public function testConstructorWithEmptyMessages(): void {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$messages = [];

		$event = new NewMessagesSynchronized($account, $mailbox, $messages);

		$this->assertEmpty($event->getMessages());
	}
}
