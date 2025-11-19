<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Support;

use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class PerformanceLogger {
	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(
		ITimeFactory $timeFactory,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
		$this->timeFactory = $timeFactory;
	}

	public function start(string $task): PerformanceLoggerTask {
		return $this->startWithLogger(
			$task,
			$this->logger
		);
	}

	public function startWithLogger(string $task, LoggerInterface $logger): PerformanceLoggerTask {
		return new PerformanceLoggerTask(
			$task,
			$this->timeFactory,
			$logger
		);
	}
}
