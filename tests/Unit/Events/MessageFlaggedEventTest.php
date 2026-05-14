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
use OCA\Mail\Events\MessageFlaggedEvent;

class MessageFlaggedEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$message = new Message();
		$message->setUid(12345);
		$flag = 'Seen';
		$set = true;

		$event = new MessageFlaggedEvent($account, $mailbox, $message, $flag, $set);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($mailbox, $event->getMailbox());
		$this->assertSame($message, $event->getMessage());
		$this->assertSame(12345, $event->getUid());
		$this->assertSame($flag, $event->getFlag());
		$this->assertTrue($event->isSet());
	}

	public function testFlagUnset(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createStub(Mailbox::class);
		$message = new Message();
		$message->setUid(99999);
		$flag = 'Flagged';
		$set = false;

		$event = new MessageFlaggedEvent($account, $mailbox, $message, $flag, $set);

		$this->assertFalse($event->isSet());
	}

	public function testGetWebhookSerializable(): void {
		$account = $this->createStub(Account::class);
		$account->method('getId')->willReturn(7);
		$mailbox = new Mailbox();
		$mailbox->setId(13);
		$message = new Message();
		$message->setId(42);
		$message->setUid(12345);

		$event = new MessageFlaggedEvent($account, $mailbox, $message, '$junk', true);

		$this->assertSame([
			'accountId' => 7,
			'flag' => '$junk',
			'mailboxId' => 13,
			'messageId' => 42,
			'set' => true,
			'uid' => 12345,
		], $event->getWebhookSerializable());
	}
}
