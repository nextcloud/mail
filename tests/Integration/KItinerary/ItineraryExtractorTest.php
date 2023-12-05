<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Tests\Unit\KItinerary;

use ChristophWurst\KItinerary\Bin\BinaryAdapter;
use ChristophWurst\KItinerary\Flatpak\FlatpakAdapter;
use ChristophWurst\KItinerary\Sys\SysAdapter;
use ChristophWurst\Nextcloud\Testing\TestCase;
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
