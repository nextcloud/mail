<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\BackgroundJob\TrainImportanceClassifierJob;
use OCA\Mail\Db\MailAccount;
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

	public function getName(): string {
		return 'Insert background jobs for all accounts';
	}

	/**
	 * @return void
	 */
	public function run(IOutput $output) {
		/** @var MailAccount[] $accounts */
		$accounts = $this->mapper->getAllAccounts();

		$output->startProgress(count($accounts));
		foreach ($accounts as $account) {
			$this->jobList->add(SyncJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(TrainImportanceClassifierJob::class, ['accountId' => $account->getId()]);
			$this->jobList->add(PreviewEnhancementProcessingJob::class, ['accountId' => $account->getId()]);
			$output->advance();
		}
		$output->finishProgress();
	}
}
