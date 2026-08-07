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
use OCA\Mail\Events\MessageDeletedEvent;

class MessageDeletedEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$uid = 42;

		$event = new MessageDeletedEvent($account, $mailbox, $uid);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($mailbox, $event->getMailbox());
		$this->assertSame($uid, $event->getUid());
	}

	public function testGetWebhookSerializable(): void {
		$account = $this->createStub(Account::class);
		$account->method('getId')->willReturn(7);
		$mailbox = new Mailbox();
		$mailbox->setId(13);

		$event = new MessageDeletedEvent($account, $mailbox, 42);

		$this->assertSame([
			'accountId' => 7,
			'mailboxId' => 13,
			'uid' => 42,
		], $event->getWebhookSerializable());
	}
}
