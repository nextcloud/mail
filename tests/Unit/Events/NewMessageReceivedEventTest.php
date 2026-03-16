<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Events;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Events\NewMessageReceivedEvent;

class NewMessageReceivedEventTest extends TestCase {
	public function testConstructorAndGetter(): void {
		$uri = 'imap://user@example.com/INBOX;UID=123';

		$event = new NewMessageReceivedEvent($uri);

		$this->assertSame($uri, $event->getUri());
	}
}
