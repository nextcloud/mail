<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixBackgroundJobs implements IRepairStep {
	/** @var IJobList */
	private $jobList;
	/** @var MailAccountMapper */
	private $mapper;

	public function __construct(
		IJobList $jobList,
		MailAccountMapper $mapper,
		private ITimeFactory $timeFactory
	) {
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
			$account->scheduleBackgroundJobs($this->jobList, $this->timeFactory);
			$output->advance();
		}
		$output->finishProgress();
	}
}
