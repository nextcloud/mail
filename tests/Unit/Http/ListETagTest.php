<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Http;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TrustedSender;
use OCA\Mail\Http\ListETag;

class ListETagTest extends TestCase {
	public function testIsTheSameForAnUnchangedList(): void {
		$etag = ListETag::of([self::trustedSender('alice@example.test')]);

		$sameEtag = ListETag::of([self::trustedSender('alice@example.test')]);

		$this->assertSame($etag, $sameEtag);
	}

	public function testChangesWithTheList(): void {
		$etag = ListETag::of([self::trustedSender('alice@example.test')]);

		$changedEtag = ListETag::of([self::trustedSender('alice@example.test'), self::trustedSender('bob@example.test')]);

		$this->assertNotSame($etag, $changedEtag);
	}

	public function testHeaderIsTheETagAsTheBrowserSendsItBack(): void {
		$list = [self::trustedSender('alice@example.test')];

		$header = ListETag::header($list);

		$this->assertSame('"' . ListETag::of($list) . '"', $header);
	}

	private static function trustedSender(string $email): TrustedSender {
		$trustedSender = new TrustedSender();
		$trustedSender->setEmail($email);
		$trustedSender->setType('individual');
		return $trustedSender;
	}
}
