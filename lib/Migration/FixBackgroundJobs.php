<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AccountService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use function method_exists;

class FixBackgroundJobs implements IRepairStep {
	public function __construct(
		private MailAccountMapper $mapper,
		private AccountService $accountService,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Insert background jobs for all accounts';
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function run(IOutput $output) {
		// Skip if method does not exist yet during upgrade
		if (!method_exists($this->accountService, 'scheduleBackgroundJobs')) {
			return;
		}

		$accounts = $this->mapper->getAllAccounts();

		$output->startProgress(count($accounts));
		foreach ($accounts as $account) {
			$this->accountService->scheduleBackgroundJobs($account->getId());
			$output->advance();
		}

		$output->finishProgress();
	}
}
