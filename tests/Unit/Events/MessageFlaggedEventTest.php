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
use OCA\Mail\Events\MessageFlaggedEvent;

class MessageFlaggedEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$uid = 12345;
		$flag = 'Seen';
		$set = true;

		$event = new MessageFlaggedEvent($account, $mailbox, $uid, $flag, $set);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($mailbox, $event->getMailbox());
		$this->assertSame($uid, $event->getUid());
		$this->assertSame($flag, $event->getFlag());
		$this->assertTrue($event->isSet());
	}

	public function testFlagUnset(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$uid = 99999;
		$flag = 'Flagged';
		$set = false;

		$event = new MessageFlaggedEvent($account, $mailbox, $uid, $flag, $set);

		$this->assertFalse($event->isSet());
	}
}
