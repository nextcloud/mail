<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MessageRetention;

class MessageRetentionTest extends TestCase {
	private MessageRetention $messageRetention;

	protected function setUp(): void {
		parent::setUp();

		$this->messageRetention = new MessageRetention();
	}

	public function testConstructor(): void {
		$messageRetention = new MessageRetention();

		$this->assertInstanceOf(MessageRetention::class, $messageRetention);
	}

	public function testSetAndGetMailboxId(): void {
		$mailboxId = 123;

		$this->messageRetention->setMailboxId($mailboxId);
		$result = $this->messageRetention->getMailboxId();

		$this->assertSame($mailboxId, $result);
	}

	public function testSetAndGetUid(): void {
		$uid = 456;

		$this->messageRetention->setUid($uid);
		$result = $this->messageRetention->getUid();

		$this->assertSame($uid, $result);
	}

	public function testSetAndGetKnownSince(): void {
		$knownSince = 1234567890;

		$this->messageRetention->setKnownSince($knownSince);
		$result = $this->messageRetention->getKnownSince();

		$this->assertSame($knownSince, $result);
	}

	public function testMultipleSettersGetters(): void {
		$mailboxId1 = 100;
		$uid1 = 200;
		$knownSince1 = 1000;
		$mailboxId2 = 101;
		$uid2 = 201;
		$knownSince2 = 2000;

		$this->messageRetention->setMailboxId($mailboxId1);
		$this->messageRetention->setUid($uid1);
		$this->messageRetention->setKnownSince($knownSince1);
		$this->assertSame($mailboxId1, $this->messageRetention->getMailboxId());
		$this->assertSame($uid1, $this->messageRetention->getUid());
		$this->assertSame($knownSince1, $this->messageRetention->getKnownSince());

		$this->messageRetention->setMailboxId($mailboxId2);
		$this->messageRetention->setUid($uid2);
		$this->messageRetention->setKnownSince($knownSince2);
		$this->assertSame($mailboxId2, $this->messageRetention->getMailboxId());
		$this->assertSame($uid2, $this->messageRetention->getUid());
		$this->assertSame($knownSince2, $this->messageRetention->getKnownSince());
	}

	public function testSetAndGetMailboxIdZero(): void {
		$mailboxId = 0;

		$this->messageRetention->setMailboxId($mailboxId);
		$result = $this->messageRetention->getMailboxId();

		$this->assertSame($mailboxId, $result);
	}

	public function testSetAndGetUidZero(): void {
		$uid = 0;

		$this->messageRetention->setUid($uid);
		$result = $this->messageRetention->getUid();

		$this->assertSame($uid, $result);
	}

	public function testSetAndGetKnownSinceZero(): void {
		$knownSince = 0;

		$this->messageRetention->setKnownSince($knownSince);
		$result = $this->messageRetention->getKnownSince();

		$this->assertSame($knownSince, $result);
	}

	public function testSetAndGetLargeMailboxId(): void {
		$mailboxId = 9223372036854775807;

		$this->messageRetention->setMailboxId($mailboxId);
		$result = $this->messageRetention->getMailboxId();

		$this->assertSame($mailboxId, $result);
	}
}
