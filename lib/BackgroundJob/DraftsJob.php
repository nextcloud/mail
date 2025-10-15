<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\DraftsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class DraftsJob extends TimedJob {
	private DraftsService $draftsService;

	public function __construct(ITimeFactory $time,
		DraftsService $draftsService) {
		parent::__construct($time);

		// Run once per five minutes
		$this->setInterval(5 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
		$this->draftsService = $draftsService;
	}

	#[\Override]
	protected function run($argument): void {
		$this->draftsService->flush();
	}
}
