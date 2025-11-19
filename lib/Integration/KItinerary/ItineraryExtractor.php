<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Integration\KItinerary;

use Nextcloud\KItinerary\Adapter;
use Nextcloud\KItinerary\Exception\KItineraryRuntimeException;
use Nextcloud\KItinerary\Itinerary;
use Nextcloud\KItinerary\ItineraryExtractor as Extractor;

class ItineraryExtractor {
	private \Nextcloud\KItinerary\Adapter|bool|null $adapter = null;

	public function __construct(
		private readonly \Nextcloud\KItinerary\Bin\BinaryAdapter $binAdapter,
		private readonly \Nextcloud\KItinerary\Flatpak\FlatpakAdapter $flatpakAdapter,
		private readonly \Nextcloud\KItinerary\Sys\SysAdapter $sysAdapter,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
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
