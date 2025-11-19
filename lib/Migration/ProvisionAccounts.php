<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Service\AccountService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ProvisionAccounts implements IRepairStep {
	public function __construct(
		private readonly \OCA\Mail\Service\Provisioning\Manager $provisioningManager
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Create or update provisioned Mail accounts';
	}

	#[\Override]
	public function run(IOutput $output): void {
		// Skip if method does not exist yet during upgrade
		if (!method_exists(AccountService::class, 'scheduleBackgroundJobs')) {
			return;
		}

		$cnt = $this->provisioningManager->provision();
		$output->info("$cnt accounts provisioned");
	}
}
