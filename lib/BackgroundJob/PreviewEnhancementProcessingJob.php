<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use function sprintf;

class PreviewEnhancementProcessingJob extends TimedJob {
	private IUserManager $userManager;
	private AccountService $accountService;
	private LoggerInterface $logger;
	private IJobList $jobList;
	private PreprocessingService $preprocessingService;

	public function __construct(ITimeFactory $time,
		IUserManager $userManager,
		AccountService $accountService,
		PreprocessingService $preprocessingService,
		LoggerInterface $logger,
		IJobList $jobList) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->preprocessingService = $preprocessingService;

		$this->setInterval(3600);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function run($argument) {
		$accountId = (int)$argument['accountId'];

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			$this->logger->info('Ignoring preprocessing job for provisioned account as athentication on IMAP not possible');
			return;
		}

		$user = $this->userManager->get($account->getUserId());
		if ($user === null || !$user->isEnabled()) {
			$this->logger->debug(sprintf(
				'Account %d of user %s could not be found or was disabled, skipping preprocessing of messages',
				$account->getId(),
				$account->getUserId()
			));
			return;
		}

		$limitTimestamp = $this->time->getTime() - (60 * 60 * 24 * 14); // Two weeks into the past
		$this->preprocessingService->process($limitTimestamp, $account);
	}
}
