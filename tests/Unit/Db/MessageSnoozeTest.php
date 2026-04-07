<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use OCA\Mail\Db\MessageSnooze;
use PHPUnit\Framework\TestCase;

final class MessageSnoozeTest extends TestCase {
	private MessageSnooze $entity;

	protected function setUp(): void {
		$this->entity = new MessageSnooze();
	}

	public function testSetGetMailboxId(): void {
		$mailboxId = 42;

		$this->entity->setMailboxId($mailboxId);
		$result = $this->entity->getMailboxId();

		$this->assertSame($mailboxId, $result);
		$this->assertIsInt($result);
	}

	public function testSetGetUid(): void {
		$uid = 12345;

		$this->entity->setUid($uid);
		$result = $this->entity->getUid();

		$this->assertSame($uid, $result);
		$this->assertIsInt($result);
	}

	public function testSetGetSnoozedUntil(): void {
		$timestamp = 1234567890;

		$this->entity->setSnoozedUntil($timestamp);
		$result = $this->entity->getSnoozedUntil();

		$this->assertSame($timestamp, $result);
		$this->assertIsInt($result);
	}

	public function testSetGetSrcMailboxId(): void {
		$srcMailboxId = 99;

		$this->entity->setSrcMailboxId($srcMailboxId);
		$result = $this->entity->getSrcMailboxId();

		$this->assertSame($srcMailboxId, $result);
		$this->assertIsInt($result);
	}

	public function testSetZeroValues(): void {
		$this->entity->setMailboxId(0);
		$this->entity->setUid(0);
		$this->entity->setSnoozedUntil(0);
		$this->entity->setSrcMailboxId(0);

		$this->assertSame(0, $this->entity->getMailboxId());
		$this->assertSame(0, $this->entity->getUid());
		$this->assertSame(0, $this->entity->getSnoozedUntil());
		$this->assertSame(0, $this->entity->getSrcMailboxId());
	}

	public function testSetLargeIntegerValues(): void {
		$largeInt = 9223372036854775807; // Max int64

		$this->entity->setMailboxId($largeInt);
		$this->entity->setUid($largeInt);
		$this->entity->setSnoozedUntil($largeInt);
		$this->entity->setSrcMailboxId($largeInt);

		$this->assertSame($largeInt, $this->entity->getMailboxId());
		$this->assertSame($largeInt, $this->entity->getUid());
		$this->assertSame($largeInt, $this->entity->getSnoozedUntil());
		$this->assertSame($largeInt, $this->entity->getSrcMailboxId());
	}

	public function testMultipleSetCallsOverwriteValue(): void {
		$this->entity->setMailboxId(10);
		$this->entity->setMailboxId(20);
		$this->entity->setMailboxId(30);

		$this->assertSame(30, $this->entity->getMailboxId());
	}

	public function testAllPropertiesIndependent(): void {
		$this->entity->setMailboxId(1);
		$this->entity->setUid(2);
		$this->entity->setSnoozedUntil(3);
		$this->entity->setSrcMailboxId(4);

		$this->assertSame(1, $this->entity->getMailboxId());
		$this->assertSame(2, $this->entity->getUid());
		$this->assertSame(3, $this->entity->getSnoozedUntil());
		$this->assertSame(4, $this->entity->getSrcMailboxId());
	}

	public function testNegativeValues(): void {
		$this->entity->setMailboxId(-1);
		$this->entity->setUid(-100);
		$this->entity->setSnoozedUntil(-999);
		$this->entity->setSrcMailboxId(-5);

		$this->assertSame(-1, $this->entity->getMailboxId());
		$this->assertSame(-100, $this->entity->getUid());
		$this->assertSame(-999, $this->entity->getSnoozedUntil());
		$this->assertSame(-5, $this->entity->getSrcMailboxId());
	}
}
