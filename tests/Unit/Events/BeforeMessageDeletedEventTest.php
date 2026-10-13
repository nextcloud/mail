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
use OCA\Mail\Events\BeforeMessageDeletedEvent;

class BeforeMessageDeletedEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);

		$mailbox = new Mailbox();
		$mailbox->setId(42);
		$mailbox->setName('INBOX');

		$uid = 123;

		$event = new BeforeMessageDeletedEvent($account, $mailbox, $uid);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($mailbox, $event->getMailbox());
		$this->assertSame('INBOX', $event->getFolderId());
		$this->assertSame($uid, $event->getUid());
	}
}
