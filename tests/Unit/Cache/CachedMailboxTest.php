<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Cache;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Cache\CachedMailbox;

class CachedMailboxTest extends TestCase {
	public function testConstructorDefaults(): void {
		$mailbox = new CachedMailbox();

		$this->assertNull($mailbox->getUids());
		$this->assertNull($mailbox->getUidValidity());
		$this->assertNull($mailbox->getHighestModSeq());
	}

	public function testSetAndGetUids(): void {
		$mailbox = new CachedMailbox();
		$uids = [1, 2, 3, 4, 5];

		$mailbox->setUids($uids);

		$this->assertSame($uids, $mailbox->getUids());
	}

	public function testSetAndGetUidValidity(): void {
		$mailbox = new CachedMailbox();
		$uidValidity = 123456;

		$mailbox->setUidValidity($uidValidity);

		$this->assertSame($uidValidity, $mailbox->getUidValidity());
	}

	public function testSetAndGetHighestModSeq(): void {
		$mailbox = new CachedMailbox();
		$modSeq = 987654;

		$mailbox->setHighestModSeq($modSeq);

		$this->assertSame($modSeq, $mailbox->getHighestModSeq());
	}

	public function testSetNullValues(): void {
		$mailbox = new CachedMailbox();
		$mailbox->setUids([1, 2, 3]);
		$mailbox->setUidValidity(123);
		$mailbox->setHighestModSeq(456);

		$mailbox->setUids(null);
		$mailbox->setUidValidity(null);
		$mailbox->setHighestModSeq(null);

		$this->assertNull($mailbox->getUids());
		$this->assertNull($mailbox->getUidValidity());
		$this->assertNull($mailbox->getHighestModSeq());
	}

	public function testSetEmptyUidArray(): void {
		$mailbox = new CachedMailbox();
		$uids = [];

		$mailbox->setUids($uids);

		$this->assertSame([], $mailbox->getUids());
	}
}
