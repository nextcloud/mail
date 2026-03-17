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
use OCA\Mail\Events\DraftSavedEvent;
use OCA\Mail\Model\NewMessageData;

class DraftSavedEventTest extends TestCase {
	public function testConstructorWithAllParams(): void {
		$account = $this->createMock(Account::class);
		$newMessageData = $this->createMock(NewMessageData::class);
		$draft = $this->createMock(Message::class);

		$event = new DraftSavedEvent($account, $newMessageData, $draft);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($newMessageData, $event->getNewMessageData());
		$this->assertSame($draft, $event->getDraft());
	}

	public function testConstructorWithoutOptionalParams(): void {
		$account = $this->createMock(Account::class);

		$event = new DraftSavedEvent($account);

		$this->assertSame($account, $event->getAccount());
		$this->assertNull($event->getNewMessageData());
		$this->assertNull($event->getDraft());
	}

	public function testConstructorWithPartialParams(): void {
		$account = $this->createMock(Account::class);
		$newMessageData = $this->createMock(NewMessageData::class);

		$event = new DraftSavedEvent($account, $newMessageData);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($newMessageData, $event->getNewMessageData());
		$this->assertNull($event->getDraft());
	}
}
