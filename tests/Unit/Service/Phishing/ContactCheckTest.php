<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;

use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class ContactCheckTest extends TestCase {

	private IL10N|MockObject $l10n;
	private ContactsIntegration|MockObject $contactsIntegration;
	private ContactCheck|MockObject $service;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->service = new ContactCheck($this->contactsIntegration, $this->l10n);
	}

	public function testContactInABCorrectEmail(): void {
		$fn = 'John Doe';
		$email = 'jhon@example.com' ;
		$contacts = [
			[
				'email' => ['jhon@example.com']
			]
		];
		$this->contactsIntegration->expects(self::once())
			->method('getContactsWithName')
			->with($fn)
			->willReturn($contacts);
		$result = $this->service->run($fn, $email);

		$this->assertFalse($result->isPhishing());
	}

	public function testCaseInsensitiveEmail(): void {
		$fn = 'John Doe';
		$email = 'jhondoe@example.com' ;
		$contacts = [
			[
				'email' => ['JhonDoe@example.com']
			]
		];
		$this->contactsIntegration
			->expects(self::once())
			->method('getContactsWithName')
			->with($fn)
			->willReturn($contacts);
		$result = $this->service->run($fn, $email);

		$this->assertFalse($result->isPhishing());
	}

	public function testContactInABWrongEmail(): void {
		$fn = 'John Doe';
		$email = 'jhon@example.com' ;
		$contacts = [
			[
				'email' => ['jhonDoe@example.com']
			]
		];
		$this->contactsIntegration->expects(self::once())
			->method('getContactsWithName')
			->with($fn)
			->willReturn($contacts);

		$result = $this->service->run($fn, $email);

		$this->assertTrue($result->isPhishing());
	}

	public function testContactNotInAB(): void {
		$fn = 'John Doe';
		$email = 'jhon@example.com' ;
		$contacts = [];
		$this->contactsIntegration
			->expects(self::once())
			->method('getContactsWithName')
			->with($fn)
			->willReturn($contacts);

		$result = $this->service->run($fn, $email);

		$this->assertFalse($result->isPhishing());
	}

}
