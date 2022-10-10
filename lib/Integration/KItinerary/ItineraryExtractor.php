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

namespace OCA\Mail\Integration\KItinerary;

use ChristophWurst\KItinerary\Adapter;
use ChristophWurst\KItinerary\Exception\KItineraryRuntimeException;
use ChristophWurst\KItinerary\Flatpak\FlatpakAdapter;
use ChristophWurst\KItinerary\Itinerary;
use ChristophWurst\KItinerary\ItineraryExtractor as Extractor;
use ChristophWurst\KItinerary\Bin\BinaryAdapter;
use ChristophWurst\KItinerary\Sys\SysAdapter;
use Psr\Log\LoggerInterface;

class ItineraryExtractor {
	/** @var BinaryAdapter */
	private $binAdapter;

	/** @var FlatpakAdapter */
	private $flatpakAdapter;

	/** @var LoggerInterface */
	private $logger;

	/** @var SysAdapter */
	private $sysAdapter;

	/** @var Adapter */
	private $adapter;

	public function __construct(BinaryAdapter $binAdapter,
								FlatpakAdapter $flatpakAdapter,
								SysAdapter $sysAdapter,
								LoggerInterface $logger) {
		$this->binAdapter = $binAdapter;
		$this->flatpakAdapter = $flatpakAdapter;
		$this->sysAdapter = $sysAdapter;
		$this->logger = $logger;
	}

	private function findAvailableAdapter(): ?Adapter {
		if ($this->binAdapter->isAvailable()) {
			$this->binAdapter->setLogger($this->logger);
			return $this->binAdapter;
		}
		if ($this->flatpakAdapter->isAvailable()) {
			return $this->flatpakAdapter;
		}
		if ($this->sysAdapter->isAvailable()) {
			$this->sysAdapter->setLogger($this->logger);
			return $this->sysAdapter;
		}
		return null;
	}

	public function extract(string $content): Itinerary {
		if ($this->adapter === null) {
			$this->adapter = $this->findAvailableAdapter() ?? false;
		}
		if ($this->adapter === false) {
			$this->logger->info('KItinerary binary adapter is not available, can\'t extract information');

			return new Itinerary();
		}

		try {
			return (new Extractor($this->adapter))->extractFromString($content);
		} catch (KItineraryRuntimeException $e) {
			$this->logger->error('Could not extract itinerary function from KItinerary integration: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return new Itinerary();
		}
	}
}
