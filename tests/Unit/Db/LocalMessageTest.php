<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\LocalMessage;

final class LocalMessageTest extends TestCase {
	public function testRejectedRecipientsDefaultToEmpty(): void {
		$message = new LocalMessage();
		$message->setRecipients([]);

		$this->assertSame([], $message->getRejectedRecipients());
		$this->assertSame([], $message->jsonSerialize()['rejectedRecipients']);
	}

	public function testJsonSerializeIncludesRejectedRecipients(): void {
		$message = new LocalMessage();
		$message->setRecipients([]);
		$message->setRejectedRecipients(['unknown@example.com']);

		$this->assertSame(['unknown@example.com'], $message->jsonSerialize()['rejectedRecipients']);
	}

	public function testRejectedRecipientsAreNotPersistedAsFields(): void {
		$message = new LocalMessage();
		$message->setRejectedRecipients(['unknown@example.com']);

		$this->assertArrayNotHasKey('rejectedRecipients', $message->getUpdatedFields());
	}
}
