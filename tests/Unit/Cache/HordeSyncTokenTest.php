<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Cache;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Cache\HordeSyncToken;

class HordeSyncTokenTest extends TestCase {
	public function testConstructorAndGetters(): void {
		$nextUid = 1234;
		$uidValidity = 5678;
		$highestModSeq = 9999;

		$token = new HordeSyncToken($nextUid, $uidValidity, $highestModSeq);

		$this->assertSame($nextUid, $token->getNextUid());
		$this->assertSame($uidValidity, $token->getUidValidity());
		$this->assertSame($highestModSeq, $token->getHighestModSeq());
	}

	public function testConstructorWithNullValues(): void {
		$token = new HordeSyncToken(null, null, null);

		$this->assertNull($token->getNextUid());
		$this->assertNull($token->getUidValidity());
		$this->assertNull($token->getHighestModSeq());
	}

	public function testConstructorWithPartialValues(): void {
		$nextUid = 100;
		$uidValidity = 200;

		$token = new HordeSyncToken($nextUid, $uidValidity, null);

		$this->assertSame($nextUid, $token->getNextUid());
		$this->assertSame($uidValidity, $token->getUidValidity());
		$this->assertNull($token->getHighestModSeq());
	}
}
