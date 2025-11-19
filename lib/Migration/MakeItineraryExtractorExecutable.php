<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Exception;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Throwable;
use function chmod;
use function is_executable;
use function is_file;

class MakeItineraryExtractorExecutable implements IRepairStep {
	private readonly string $file;

	public function __construct(
		private readonly \Psr\Log\LoggerInterface $logger,
		?string $file = null
	) {
		$this->file = $file ?? (__DIR__ . '/../../vendor/nextcloud/kitinerary-bin/bin/kitinerary-extractor');
	}

	#[\Override]
	public function getName(): string {
		return 'Make Mail itinerary extractor executable';
	}

	#[\Override]
	public function run(IOutput $output): void {
		if (!is_file($this->file)) {
			$this->logger->warning('itinerary file doesn\'t exist');
			$output->info('itinerary file doesn\'t exist');
			return;
		}
		if (is_executable($this->file)) {
			$this->logger->debug('itinerary is already executable');
			return;
		}
		try {
			if (!@chmod($this->file, 0744)) {
				throw new Exception('chmod returned false');
			}
		} catch (Throwable $e) {
			$this->logger->error('Can\'t make itinerary extractor executable: ' . $e, [
				'exception' => $e,
			]);
			$output->warning('Can\'t make itinerary extractor executable: ' . $e->getMessage());
			return;
		}
	}
}
