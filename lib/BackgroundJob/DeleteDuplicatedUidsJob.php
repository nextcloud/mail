<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Db\MessageMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class DeleteDuplicatedUidsJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private MessageMapper $messageMapper,
	) {
		parent::__construct($time);
	}

	#[\Override]
	protected function run($argument): void {
		$this->messageMapper->deleteDuplicateUids();
	}
}
