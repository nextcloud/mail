<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AccountService;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use function method_exists;

/**
 * @psalm-api
 */
class FixBackgroundJobs implements IRepairStep {
	public function __construct(
		private MailAccountMapper $mapper,
		private AccountService $accountService,
		private IUserManager $userManager,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Reconcile background jobs for all accounts';
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function run(IOutput $output) {
		// Skip if methods do not exist yet during upgrade
		if (!method_exists($this->accountService, 'scheduleBackgroundJobs')
			|| !method_exists($this->accountService, 'removeBackgroundJobs')) {
			return;
		}

		$accounts = $this->mapper->getAllAccounts();

		$output->startProgress(count($accounts));
		foreach ($accounts as $account) {
			$user = $this->userManager->get($account->getUserId());
			if ($user === null || !$user->isEnabled()) {
				$this->accountService->removeBackgroundJobs($account->getId());
			} else {
				$this->accountService->scheduleBackgroundJobs($account->getId());
			}
			$output->advance();
		}

		$output->finishProgress();
	}
}
