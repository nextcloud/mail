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
use OCA\Mail\Events\NewMessageReceivedEvent;

class NewMessageReceivedEventTest extends TestCase {
	public function testConstructorAndGetter(): void {
		$uri = 'imap://user@example.com/INBOX;UID=123';
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$message = new Message();

		$event = new NewMessageReceivedEvent($account, $mailbox, $message, $uri);

		$this->assertSame($uri, $event->getUri());
	}

	public function testGetWebhookSerializable(): void {
		$uri = 'imap://user@example.com/INBOX;UID=123';
		$account = $this->createStub(Account::class);
		$account->method('getId')->willReturn(7);
		$mailbox = new Mailbox();
		$mailbox->setId(13);
		$message = new Message();
		$message->setId(42);
		$message->setMessageId('<abc@example.com>');
		$message->setSentAt(1770000000);
		$message->setSubject('Hello');
		$message->setInReplyTo('<parent@example.com>');
		$message->setThreadRootId('<root@example.com>');

		$event = new NewMessageReceivedEvent($account, $mailbox, $message, $uri);

		$this->assertSame([
			'accountId' => 7,
			'inReplyToRfcMessageId' => '<parent@example.com>',
			'mailboxId' => 13,
			'messageId' => 42,
			'messageUri' => $uri,
			'rfcMessageId' => '<abc@example.com>',
			'sentAt' => 1770000000,
			'subject' => 'Hello',
			'threadRootId' => '<root@example.com>',
		], $event->getWebhookSerializable());
	}
}
