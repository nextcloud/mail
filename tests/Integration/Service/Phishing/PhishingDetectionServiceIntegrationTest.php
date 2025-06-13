<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Mail\Tests\Integration\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Headers;
use OCA\Mail\Service\ContactsIntegration;

use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCA\Mail\Service\PhishingDetection\LinkCheck;
use OCA\Mail\Service\PhishingDetection\PhishingDetectionService;
use OCA\Mail\Service\PhishingDetection\ReplyToCheck;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class PhishingDetectionServiceIntegrationTest extends TestCase {

	private ContactsIntegration|MockObject $contactsIntegration;
	private IL10N|MockObject $l10n;
	private ITimeFactory $timeFactory;
	private ContactCheck $contactCheck;
	private CustomEmailCheck $customEmailCheck;
	private DateCheck $dateCheck;
	private ReplyToCheck $replyToCheck;
	private LinkCheck $linkCheck;
	private PhishingDetectionService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->contactCheck = new ContactCheck($this->contactsIntegration, $this->l10n);
		$this->customEmailCheck = new CustomEmailCheck($this->l10n);
		$this->dateCheck = new DateCheck($this->l10n, \OC::$server->get(ITimeFactory::class));
		$this->replyToCheck = new ReplyToCheck($this->l10n);
		$this->linkCheck = new LinkCheck($this->l10n);
		$this->service = new PhishingDetectionService($this->contactCheck, $this->customEmailCheck, $this->dateCheck, $this->replyToCheck, $this->linkCheck);
	}



	public function testContactCheck(): void {
		$this->contactsIntegration->expects(self::once())
			->method('getContactsWithName')
			->with('John Doe')
			->willReturn([['id' => 1, 'fn' => 'John Doe', 'email' => ['jhon@example.org','Doe@example.org']]]);

		$result = $this->contactCheck->run('John Doe', 'jhon.doe@example.org');

		$this->assertTrue($result->isPhishing());
	}

	public function testCustomEmailCheck(): void {
		$result = $this->customEmailCheck->run('jhon@example.org', 'jhon.doe@example.org');
		$this->assertTrue($result->isPhishing());
	}

	public function testReplyToCheck(): void {
		$result = $this->replyToCheck->run('jhon@example.org', 'jhon.doe@example.org');
		$this->assertTrue($result->isPhishing());
	}
	public function testCheckHeadersForPhishing(): void {
		$headerStream = fopen(__DIR__ . '/../../../data/phishing-mail-headers.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, false);
		$this->assertTrue($result['warning']);
	}

}
