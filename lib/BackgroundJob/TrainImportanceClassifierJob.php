<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\ILogger;
use Throwable;

class TrainImportanceClassifierJob extends TimedJob {

	/** @var AccountService */
	private $accountService;

	/** @var ImportanceClassifier */
	private $classifier;

	/** @var IJobList */
	private $jobList;

	/** @var ILogger */
	private $logger;

	public function __construct(ITimeFactory $time,
								AccountService $accountService,
								ImportanceClassifier $classifier,
								IJobList $jobList,
								ILogger $logger) {
		parent::__construct($time);

		$this->accountService = $accountService;
		$this->classifier = $classifier;
		$this->jobList = $jobList;
		$this->logger = $logger;

		$this->setInterval(3600);
	}

	protected function run($argument) {
		$accountId = (int)$argument['accountId'];

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		try {
			$this->classifier->train($account);
		} catch (Throwable $e) {
			$this->logger->logException($e, [
				'message' => 'Cron importance classifier training failed: ' . $e->getMessage(),
			]);
		}
	}
}
