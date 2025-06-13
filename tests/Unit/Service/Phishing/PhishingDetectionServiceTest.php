<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mime_Headers;
use OCA\Mail\PhishingDetectionResult;

use OCA\Mail\Service\PhishingDetection\ContactCheck;
use OCA\Mail\Service\PhishingDetection\CustomEmailCheck;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCA\Mail\Service\PhishingDetection\LinkCheck;
use OCA\Mail\Service\PhishingDetection\PhishingDetectionService;
use OCA\Mail\Service\PhishingDetection\ReplyToCheck;

use PHPUnit\Framework\MockObject\MockObject;

class PhishingDetectionServiceTest extends TestCase {

	private ContactCheck|MockObject $contactCheck;
	private CustomEmailCheck|MockObject $customEmailCheck;
	private DateCheck|MockObject $dateCheck;
	private ReplyToCheck|MockObject $replyToCheck;
	private LinkCheck|MockObject $linkCheck;
	private PhishingDetectionService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->contactCheck = $this->createMock(ContactCheck::class);
		$this->customEmailCheck = $this->createMock(CustomEmailCheck::class);
		$this->dateCheck = $this->createMock(DateCheck::class);
		$this->replyToCheck = $this->createMock(ReplyToCheck::class);
		$this->linkCheck = $this->createMock(LinkCheck::class);
		$this->service = new PhishingDetectionService($this->contactCheck, $this->customEmailCheck, $this->dateCheck, $this->replyToCheck, $this->linkCheck);
	}

	public function testCheckHeadersForPhishing(): void {
		$headerStream = fopen(__DIR__ . '/../../../data/phishing-mail-headers.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$this->replyToCheck->expects($this->once())
			->method('run')
			->with('jhondoe@example.com', 'batman@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false));
		$this->contactCheck->expects($this->once())
			->method('run')
			->with('Jhon Doe', 'jhondoe@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false));
		$this->dateCheck->expects($this->once())
			->method('run')
			->with('Tue, 28 May 3024 13:02:15 +0200')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false));
		$this->customEmailCheck->expects($this->never())
			->method('run');
		$this->linkCheck->expects($this->once())
			->method('run')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false));
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, true, '');
		$this->assertFalse($result['warning']);
	}

	public function testCheckHeadersForPhishingWithoutFrom(): void {
		$headerStream = fopen(__DIR__ . '/../../../data/phishing-mail-headers.no-from.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$this->replyToCheck->expects($this->never())
			->method('run');
		$this->contactCheck->expects($this->never())
			->method('run');
		$this->dateCheck->expects($this->once())
			->method('run')
			->with('Tue, 28 May 3024 13:02:15 +0200')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false));
		$this->customEmailCheck->expects($this->never())
			->method('run');
		$this->linkCheck->expects($this->once())
			->method('run')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false));
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, true, '');
		$this->assertFalse($result['warning']);
	}

	public function testCheckHeadersForPhishingWithoutReplyTo(): void {
		$headerStream = fopen(__DIR__ . '/../../../data/phishing-mail-headers.no-reply-to.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$this->replyToCheck->expects($this->never())
			->method('run');
		$this->contactCheck->expects($this->once())
			->method('run')
			->with('Jhon Doe', 'jhondoe@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false));
		$this->dateCheck->expects($this->once())
			->method('run')
			->with('Tue, 28 May 3024 13:02:15 +0200')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false));
		$this->customEmailCheck->expects($this->never())
			->method('run');
		$this->linkCheck->expects($this->once())
			->method('run')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false));
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, true, '');
		$this->assertFalse($result['warning']);
	}

	public function testCheckHeadersForPhishingWithMalformedDate(): void {
		$headerStream = fopen(__DIR__ . '/../../../data/phishing-mail-headers.malformed-date.txt', 'r');
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headerStream);
		fclose($headerStream);
		$this->replyToCheck->expects($this->once())
			->method('run')
			->with('jhondoe@example.com', 'batman@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false));
		$this->contactCheck->expects($this->once())
			->method('run')
			->with('Jhon Doe', 'jhondoe@example.com')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false));
		$this->dateCheck->expects($this->once())
			->method('run')
			->with('Wed, 26 Feb 2025 12:09:28 +0100 (GMT+01:00)')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false));
		$this->customEmailCheck->expects($this->never())
			->method('run');
		$this->linkCheck->expects($this->once())
			->method('run')
			->willReturn(new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false));
		$result = $this->service->checkHeadersForPhishing($parsedHeaders, true, '');
		$this->assertFalse($result['warning']);
	}
}
