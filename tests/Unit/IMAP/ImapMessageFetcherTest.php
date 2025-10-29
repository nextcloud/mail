<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_DateTime;
use Horde_Mime_Headers;
use OCA\Mail\IMAP\Charset\Converter;
use OCA\Mail\IMAP\ImapMessageFetcher;
use OCA\Mail\Service\Html;
use OCA\Mail\Service\PhishingDetection\PhishingDetectionService;
use OCA\Mail\Service\SmimeService;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

final class ImapMessageFetcherTest extends TestCase {
	private Html|MockObject $htmlService;
	private SmimeService|MockObject $smimeService;
	private Converter|MockObject $converter;
	private PhishingDetectionService|MockObject $phishingDetectionService;
	private Horde_Imap_Client_Base|MockObject $client;
	private ImapMessageFetcher $fetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->htmlService = $this->createMock(Html::class);
		$this->smimeService = $this->createMock(SmimeService::class);
		$this->converter = $this->createMock(Converter::class);
		$this->phishingDetectionService = $this->createMock(PhishingDetectionService::class);
		$this->client = $this->createMock(Horde_Imap_Client_Base::class);

		$this->fetcher = new ImapMessageFetcher(
			42,
			'INBOX',
			$this->client,
			'user',
			$this->htmlService,
			$this->smimeService,
			$this->converter,
			$this->phishingDetectionService,
		);
	}

	private function invokeResolveMessageDate(Horde_Imap_Client_Data_Fetch $fetch, Horde_Mime_Headers $headers): Horde_Imap_Client_DateTime {
		$method = new ReflectionMethod(ImapMessageFetcher::class, 'resolveMessageDate');
		$method->setAccessible(true);
		/** @var Horde_Imap_Client_DateTime $result */
		$result = $method->invoke($this->fetcher, $fetch, $headers);
		return $result;
	}

	public function testResolveMessageDatePrefersHeader(): void {
		$fetch = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetch->method('getImapDate')
			->willReturn(new Horde_Imap_Client_DateTime('2025-10-20 10:00:00 +0000'));
		$headers = Horde_Mime_Headers::parseHeaders("Date: Mon, 01 Jan 2001 12:00:00 +0000\r\n");

		$result = $this->invokeResolveMessageDate($fetch, $headers);

		self::assertSame('2001-01-01T12:00:00+00:00', $result->format('c'));
	}

	public function testResolveMessageDateFallsBackToInternalWithoutHeader(): void {
		$internal = new Horde_Imap_Client_DateTime('2025-10-20 10:00:00 +0000');
		$fetch = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetch->method('getImapDate')->willReturn($internal);
		$headers = Horde_Mime_Headers::parseHeaders('');

		$result = $this->invokeResolveMessageDate($fetch, $headers);

		self::assertSame($internal->format('c'), $result->format('c'));
	}

	public function testResolveMessageDateFallsBackToInternalOnInvalidHeader(): void {
		$internal = new Horde_Imap_Client_DateTime('2025-10-20 10:00:00 +0000');
		$fetch = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetch->method('getImapDate')->willReturn($internal);
		$headers = Horde_Mime_Headers::parseHeaders("Date: not-a-valid-date\r\n");

		$result = $this->invokeResolveMessageDate($fetch, $headers);

		self::assertSame($internal->format('c'), $result->format('c'));
	}

	public function testResolveMessageDateFallsBackToNowWhenNoDateAvailable(): void {
		$fetch = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetch->method('getImapDate')->willReturn(null);
		$headers = Horde_Mime_Headers::parseHeaders('');

		$before = time();
		$result = $this->invokeResolveMessageDate($fetch, $headers);
		$after = time();

		self::assertGreaterThanOrEqual($before, $result->getTimestamp());
		self::assertLessThanOrEqual($after, $result->getTimestamp());
	}
}
