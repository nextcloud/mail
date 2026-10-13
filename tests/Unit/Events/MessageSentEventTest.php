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
		$account = $this->createStub(Account::class);
		$localMessage = $this->createStub(LocalMessage::class);

		$event = new MessageSentEvent($account, $localMessage);

		$this->assertSame($account, $event->getAccount());
		$this->assertSame($localMessage, $event->getLocalMessage());
	}

	public function testGetWebhookSerializable(): void {
		$account = $this->createStub(Account::class);
		$account->method('getId')->willReturn(7);
		$localMessage = new LocalMessage();
		$localMessage->setSendAt(1770000000);
		$localMessage->setSubject('Hello');
		$localMessage->setInReplyToMessageId('<parent@example.com>');

		$event = new MessageSentEvent($account, $localMessage);

		$this->assertSame([
			'accountId' => 7,
			'inReplyToRfcMessageId' => '<parent@example.com>',
			'sendAt' => 1770000000,
			'subject' => 'Hello',
		], $event->getWebhookSerializable());
	}
}
