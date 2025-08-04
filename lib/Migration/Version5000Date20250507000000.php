<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5000Date20250507000000 extends SimpleMigrationStep {

	public function __construct(
		private IJobList $jobService,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$this->jobService->add(\OCA\Mail\BackgroundJob\RepairRecipients::class);
	}

}
