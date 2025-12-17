<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;

use Horde_Imap_Client;
use OCA\Mail\Service\PhishingDetection\ImapFlagCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class ImapFlagCheckTest extends TestCase {

	private IL10N|MockObject $l10n;
	private ImapFlagCheck $service;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->service = new ImapFlagCheck($this->l10n);
	}

	public function testNoFlags(): void {
		$flags = [];
		$result = $this->service->run($flags);

		$this->assertFalse($result->isPhishing());
	}

	public function testOnlySpamFlag(): void {
		$flags = [Horde_Imap_Client::FLAG_JUNK];
		$result = $this->service->run($flags);

		$this->assertFalse($result->isPhishing());
	}

	public function testOnlyPhishingFlag(): void {
		// TODO: Use Horde const once the flag is implemented there
		//  (https://github.com/bytestream/Imap_Client/blob/master/lib/Horde/Imap/Client.php#L153).
		$flags = ['$phishing'];
		$result = $this->service->run($flags);

		$this->assertFalse($result->isPhishing());
	}

	public function testSpamAndPhishingFlag(): void {
		// TODO: Use Horde const for $phishing once the flag is implemented there
		//  (https://github.com/bytestream/Imap_Client/blob/master/lib/Horde/Imap/Client.php#L153).
		$flags = [Horde_Imap_Client::FLAG_JUNK, '$phishing'];
		$result = $this->service->run($flags);

		$this->assertTrue($result->isPhishing());
	}

	public function testThunderbirdSpamAndPhishingFlag(): void {
		// TODO: Use Horde const for $phishing once the flag is implemented there
		//  (https://github.com/bytestream/Imap_Client/blob/master/lib/Horde/Imap/Client.php#L153).
		$flags = ['junk', '$phishing'];
		$result = $this->service->run($flags);

		$this->assertTrue($result->isPhishing());
	}
}
