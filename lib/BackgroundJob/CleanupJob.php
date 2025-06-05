<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\CleanupService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class CleanupJob extends TimedJob {
	private CleanupService $cleanupService;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $time,
		CleanupService $cleanupService,
		LoggerInterface $logger) {
		parent::__construct($time);
		$this->cleanupService = $cleanupService;
		$this->logger = $logger;

		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$this->cleanupService->cleanUp($this->logger);
	}
}
