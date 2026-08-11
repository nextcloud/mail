<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\KItinerary;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Nextcloud\KItinerary\Bin\BinaryAdapter;
use Nextcloud\KItinerary\Flatpak\FlatpakAdapter;
use Nextcloud\KItinerary\Sys\SysAdapter;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ItineraryExtractorTest extends TestCase {
	/** @var BinaryAdapter|MockObject */
	private $binaryAdapter;

	/** @var FlatpakAdapter|MockObject */
	private $flatpakAdapter;

	/** @var SysAdapter|MockObject */
	private $sysAdapter;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var ItineraryExtractor */
	private $extractor;

	protected function setUp(): void {
		parent::setUp();

		$this->binaryAdapter = $this->createMock(BinaryAdapter::class);
		$this->flatpakAdapter = $this->createMock(FlatpakAdapter::class);
		$this->sysAdapter = $this->createMock(SysAdapter::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->extractor = new ItineraryExtractor(
			$this->binaryAdapter,
			$this->flatpakAdapter,
			$this->sysAdapter,
			$this->logger
		);
	}

	public function testNoAdapterAvailable() {
		$this->binaryAdapter->expects($this->never())
			->method('extractFromString');
		$this->flatpakAdapter->expects($this->never())
			->method('extractFromString');

		$itinerary = $this->extractor->extract('');

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testBinAvailable() {
		$this->binaryAdapter->expects($this->once())
			->method('isAvailable')
			->willReturn(true);
		$this->binaryAdapter->expects($this->once())
			->method('extractFromString')
			->with('data');
		$this->flatpakAdapter->expects($this->never())
			->method('isAvailable');
		$this->flatpakAdapter->expects($this->never())
			->method('extractFromString');

		$itinerary = $this->extractor->extract('data');

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testFlatpakAvailable() {
		$this->binaryAdapter->expects($this->never())
			->method('extractFromString');
		$this->flatpakAdapter->expects($this->once())
			->method('isAvailable')
			->willReturn(true);
		$this->flatpakAdapter->expects($this->once())
			->method('extractFromString');

		$itinerary = $this->extractor->extract('data');

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testSysAvailable() {
		$this->binaryAdapter->expects($this->never())
			->method('extractFromString');
		$this->sysAdapter->expects($this->once())
			->method('isAvailable')
			->willReturn(true);
		$this->sysAdapter->expects($this->once())
			->method('extractFromString');

		$itinerary = $this->extractor->extract('data');

		$this->assertEquals([], $itinerary->jsonSerialize());
	}
}
