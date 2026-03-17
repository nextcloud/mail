<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\PhishingDetection;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCP\IL10N;

class ContactCheckTest extends TestCase {
	private ContactCheck $check;
	private ContactsIntegration $contactsIntegration;
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->check = new ContactCheck($this->contactsIntegration, $this->l10n);
	}

	public function testExactEmailMatchReturnsNonPhishing(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1', 'email' => ['sender@example.com']],
		]);

		$result = $this->check->run('Sender Name', 'sender@example.com');

		$this->assertFalse($result->isPhishing());
	}

	public function testNoContactMatchReturnsNonPhishing(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([]);

		$result = $this->check->run('Unknown Name', 'unknown@example.com');

		$this->assertFalse($result->isPhishing());
	}

	public function testDifferentEmailSingleContactReturnsPhishing(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1', 'email' => ['different@example.com']],
		]);

		$result = $this->check->run('Sender Name', 'sender@example.com');

		$this->assertTrue($result->isPhishing());
	}

	public function testDifferentEmailMultipleContactsReturnsPhishing(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1', 'email' => ['email1@example.com', 'email2@example.com']],
		]);

		$result = $this->check->run('Sender Name', 'sender@example.com');

		$this->assertTrue($result->isPhishing());
	}

	public function testCaseInsensitiveEmailComparison(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1', 'email' => ['Test@Example.com']],
		]);

		$result = $this->check->run('Sender Name', 'test@example.com');

		$this->assertFalse($result->isPhishing());
	}

	public function testContactMissingEmailFieldSkipped(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1'],
		]);

		$result = $this->check->run('Sender Name', 'sender@example.com');

		$this->assertFalse($result->isPhishing());
	}

	public function testMultipleContacts(): void {
		$this->contactsIntegration->method('getContactsWithName')->willReturn([
			['uid' => 'contact1', 'email' => ['email1@example.com']],
			['uid' => 'contact2', 'email' => ['email2@example.com']],
		]);

		$result = $this->check->run('Sender Name', 'sender@example.com');

		$this->assertTrue($result->isPhishing());
	}
}
