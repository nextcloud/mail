<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\IMipService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class IMipMessageJob extends TimedJob {
	private IMipService $iMipService;

	public function __construct(ITimeFactory $time,
		IMipService $iMipService) {
		parent::__construct($time);

		$this->setInterval(300);
		$this->iMipService = $iMipService;
	}

	#[\Override]
	protected function run($argument): void {
		$this->iMipService->process();
	}
}
