<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\PhishingDetection;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use OCA\Mail\Service\PhishingDetection\ImapFlagCheck;
use OCP\IL10N;

class ImapFlagCheckTest extends TestCase {
	private ImapFlagCheck $check;
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->check = new ImapFlagCheck($this->l10n);
	}

	public function testEmptyFlagsReturnsSafe(): void {
		$result = $this->check->run([]);

		$this->assertFalse($result->isPhishing());
	}

	public function testOnlyJunkFlagReturnsSafe(): void {
		$result = $this->check->run([Horde_Imap_Client::FLAG_JUNK]);

		$this->assertFalse($result->isPhishing());
	}

	public function testOnlyPhishingFlagReturnsSafe(): void {
		$result = $this->check->run(['$phishing']);

		$this->assertFalse($result->isPhishing());
	}

	public function testBothFlagsReturnsWarning(): void {
		$result = $this->check->run([Horde_Imap_Client::FLAG_JUNK, '$phishing']);

		$this->assertTrue($result->isPhishing());
	}

	public function testJunkStringAndPhishingFlagReturnsWarning(): void {
		$result = $this->check->run(['junk', '$phishing']);

		$this->assertTrue($result->isPhishing());
	}
}
