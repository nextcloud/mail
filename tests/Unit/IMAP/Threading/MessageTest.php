<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP\Threading;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\Threading\Message;

class MessageTest extends TestCase {
	public function testGetId(): void {
		$message = new Message('', 'id', []);

		$this->assertSame('id', $message->getId());
	}

	public function getGetReferences(): void {
		$message = new Message('', 'id', ['ref1', 'ref2']);

		$this->assertEquals(['ref1', 'ref2'], $message->getReferences());
	}
}
