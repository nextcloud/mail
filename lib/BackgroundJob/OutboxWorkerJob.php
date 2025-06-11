<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\OutboxService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class OutboxWorkerJob extends TimedJob {
	private OutboxService $outboxService;

	public function __construct(ITimeFactory $time,
		OutboxService $outboxService) {
		parent::__construct($time);

		// Run once per five minutes
		$this->setInterval(5 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
		$this->outboxService = $outboxService;
	}

	#[\Override]
	protected function run($argument): void {
		$this->outboxService->flush();
	}
}
