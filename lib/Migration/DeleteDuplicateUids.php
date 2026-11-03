<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\BackgroundJob\DeleteDuplicatedUidsJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DeleteDuplicateUids implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Queue a job to delete duplicated cached messages';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$this->jobList->add(DeleteDuplicatedUidsJob::class);
	}
}
