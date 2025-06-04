<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\SnoozeService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class WakeJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private SnoozeService $snoozeService,
	) {
		parent::__construct($time);

		$this->setInterval(60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function run($argument): void {
		$this->snoozeService->wakeMessages();
	}
}
