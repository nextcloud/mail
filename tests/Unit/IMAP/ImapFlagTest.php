<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\ImapFlag;
use PHPUnit\Framework\Attributes\DataProvider;

class ImapFlagTest extends TestCase {

	private ImapFlag $imapFlag;

	protected function setUp(): void {
		parent::setUp();

		$this->imapFlag = new ImapFlag();
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreate(string $label, string $expected): void {
		$actual = $this->imapFlag->create($label);
		$this->assertEquals($expected, $actual);
	}

	public function dataCreate(): array {
		return [
			'umlauts and lowercase' => [
				'Test ÄÖÜ',
				'$test_&amqa1gdc-'
			],
			'maximum 63 characters' => [
				'1234567890123456789012345678901234567890123456789012345678901234',
				'$123456789012345678901234567890123456789012345678901234567890123',
			],
		];
	}
}
