<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Support;

use OCP\AppFramework\Utility\ITimeFactory;
use function function_exists;
use function memory_get_peak_usage;
use function memory_get_usage;
use function round;
use function sprintf;

class PerformanceLoggerTask {
	/** @var ITimeFactory */
	private $timeFactory;

	/** @var int */
	private $start;

	/** @var int */
	private $rel;

	public function __construct(
		private readonly string $task,
		ITimeFactory $timeFactory,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
		$this->timeFactory = $timeFactory;

		$this->start = $this->rel = $timeFactory->getTime();
	}

	public function step(string $description): void {
		$now = $this->timeFactory->getTime();
		$passed = $now - $this->rel;

		$message = $this->task . " - $description took {$passed}s.";
		if (function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$this->logger->debug(
				sprintf(
					$message . ' %d/%dMB memory used',
					round(memory_get_usage() / 1024 / 1024),
					round(memory_get_peak_usage() / 1024 / 1024)
				)
			);
		} else {
			$this->logger->debug($message);
		}

		$this->rel = $now;
	}

	public function end(): int {
		$now = $this->timeFactory->getTime();
		$passed = $now - $this->start;

		$this->logger->debug($this->task . " took {$passed}s");

		return $passed;
	}
}
