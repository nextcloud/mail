<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Integration\KItinerary;

use Nextcloud\KItinerary\Adapter;
use Nextcloud\KItinerary\Bin\BinaryAdapter;
use Nextcloud\KItinerary\Exception\KItineraryRuntimeException;
use Nextcloud\KItinerary\Flatpak\FlatpakAdapter;
use Nextcloud\KItinerary\Itinerary;
use Nextcloud\KItinerary\ItineraryExtractor as Extractor;
use Nextcloud\KItinerary\Sys\SysAdapter;
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
