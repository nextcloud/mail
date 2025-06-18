<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Mailbox;

class MailboxTest extends TestCase {
	private Mailbox $mailbox;

	protected function setUp(): void {
		parent::setUp();

		$this->mailbox = new Mailbox();
	}

	public static function provideCacheBusterData(): array {
		return [
			['new', 'changed', 'vanished', 'bbddae86e09069fc10c9f2ac401363b4'],
			[null, null, null, 'dca1f7641c34734a8cd1c7b1c45abf73'],
		];
	}

	/** @dataProvider provideCacheBusterData */
	public function testGetCacheBuster(
		?string $syncNewToken,
		?string $syncChangedToken,
		?string $syncVanishedToken,
		string $expectedCacheBuster,
	): void {
		$this->mailbox->setId(100);
		$this->mailbox->setSyncNewToken($syncNewToken);
		$this->mailbox->setSyncChangedToken($syncChangedToken);
		$this->mailbox->setSyncVanishedToken($syncVanishedToken);

		$this->assertEquals($expectedCacheBuster, $this->mailbox->getCacheBuster());
	}

	/** @dataProvider provideCacheBusterData */
	public function testJsonSerializeCacheBuster(
		?string $syncNewToken,
		?string $syncChangedToken,
		?string $syncVanishedToken,
		string $expectedCacheBuster,
	): void {
		$this->mailbox->setId(100);
		$this->mailbox->setSyncNewToken($syncNewToken);
		$this->mailbox->setSyncChangedToken($syncChangedToken);
		$this->mailbox->setSyncVanishedToken($syncVanishedToken);
		$this->mailbox->setName('INBOX');

		$json = $this->mailbox->jsonSerialize();
		$this->assertArrayHasKey('cacheBuster', $json);
		$this->assertEquals($expectedCacheBuster, $json['cacheBuster']);
	}
}
