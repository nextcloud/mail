<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use Throwable;

class TrainImportanceClassifierJob extends TimedJob {
	private AccountService $accountService;
	private ImportanceClassifier $classifier;
	private IJobList $jobList;
	private LoggerInterface $logger;
	private ClassificationSettingsService $classificationSettingsService;

	public function __construct(ITimeFactory $time,
		AccountService $accountService,
		ImportanceClassifier $classifier,
		IJobList $jobList,
		LoggerInterface $logger,
		ClassificationSettingsService $classificationSettingsService) {
		parent::__construct($time);

		$this->accountService = $accountService;
		$this->classifier = $classifier;
		$this->jobList = $jobList;
		$this->logger = $logger;
		$this->classificationSettingsService = $classificationSettingsService;

		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @return void
	 */
	#[\Override]
	protected function run($argument) {
		$accountId = (int)$argument['accountId'];

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			$this->logger->debug('Cron importance classifier training not possible: no authentication on IMAP possible');
			return;
		}

		if (!$this->classificationSettingsService->isClassificationEnabled($account->getUserId())) {
			$this->logger->debug("classification is turned off for account $accountId");
			return;
		}

		try {
			$this->classifier->train($account, $this->logger);
		} catch (Throwable $e) {
			$this->logger->error('Cron importance classifier training failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
