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
use OCA\Mail\Events\SaveDraftEvent;
use OCA\Mail\Model\NewMessageData;

class SaveDraftEventTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$account = $this->createStub(Account::class);
		$newMessageData = $this->createStub(NewMessageData::class);
		$draft = $this->createStub(Message::class);

		$event = new SaveDraftEvent($account, $newMessageData, $draft);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($newMessageData, $event->getNewMessageData());
		$this->assertSame($draft, $event->getDraft());
	}

	public function testConstructorWithoutDraft(): void {
		$account = $this->createStub(Account::class);
		$newMessageData = $this->createStub(NewMessageData::class);

		$event = new SaveDraftEvent($account, $newMessageData, null);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($newMessageData, $event->getNewMessageData());
		$this->assertNull($event->getDraft());
	}
}
