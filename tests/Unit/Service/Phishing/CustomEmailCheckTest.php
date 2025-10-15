<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;

use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class CustomEmailCheckTest extends TestCase {

	private IL10N|MockObject $l10n;
	private CustomEmailCheck|MockObject $service;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->service = new CustomEmailCheck($this->l10n);
	}

	public function testNoEmail(): void {
		$email = 'jhon@example.com';
		$result = $this->service->run($email, null);

		$this->assertFalse($result->isPhishing());
	}

	public function testSameEmail(): void {
		$email = 'jhon@example.com';
		$result = $this->service->run($email, $email);

		$this->assertFalse($result->isPhishing());
	}

	public function testDifferentEmail(): void {
		$email = 'jhon@example.com';
		$customEmail = 'jhondoe@example.com';
		$result = $this->service->run($email, $customEmail);

		$this->assertTrue($result->isPhishing());
	}

}
