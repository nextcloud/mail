<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\PhishingDetection;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCP\IL10N;

class CustomEmailCheckTest extends TestCase {
	private CustomEmailCheck $check;
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->check = new CustomEmailCheck($this->l10n);
	}

	public function testNullCustomEmailReturnsSafe(): void {
		$result = $this->check->run('sender@example.com', null);

		$this->assertFalse($result->isPhishing());
	}

	public function testMatchingEmailsReturnsSafe(): void {
		$result = $this->check->run('sender@example.com', 'sender@example.com');

		$this->assertFalse($result->isPhishing());
	}

	public function testDifferentEmailsReturnsWarning(): void {
		$result = $this->check->run('sender@example.com', 'custom@example.com');

		$this->assertTrue($result->isPhishing());
	}
}
