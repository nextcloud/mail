<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\BackgroundJob\QuotaJob;
use OCA\Mail\BackgroundJob\RepairSyncJob;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\BackgroundJob\TrainImportanceClassifierJob;
use OCA\Mail\Db\MailAccountMapper;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixBackgroundJobs implements IRepairStep {
	/** @var IJobList */
	private $jobList;
	/** @var MailAccountMapper */
	private $mapper;

	public function __construct(IJobList $jobList, MailAccountMapper $mapper) {
		$this->jobList = $jobList;
		$this->mapper = $mapper;
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
		$accounts = $this->mapper->getAllAccounts();

		$output->startProgress(count($accounts));
		foreach ($accounts as $account) {
			$this->jobList->add(SyncJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(RepairSyncJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(TrainImportanceClassifierJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(PreviewEnhancementProcessingJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(QuotaJob::class, ['accountId' => $account->getId()]);
			$output->advance();
		}
		$output->finishProgress();
	}
}
