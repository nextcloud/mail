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
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use function max;
use function sprintf;

class PreviewEnhancementProcessingJob extends TimedJob {
	private const DEFAULT_INTERVAL = 3600;
	private const MIN_INTERVAL = 60;

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
		IJobList $jobList,
		IConfig $config) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->preprocessingService = $preprocessingService;

		// Allow admins to tighten the interval on setups where preview data
		// directly gates user-visible behaviour (e.g. the imip_message flag
		// that IMipMessageJob depends on to turn incoming invitations into
		// calendar events). A floor of MIN_INTERVAL keeps a misconfigured
		// value from hammering the DB.
		$configured = $config->getSystemValueInt(
			'app.mail.preview-enhancement-interval',
			self::DEFAULT_INTERVAL,
		);
		$this->setInterval(max(self::MIN_INTERVAL, $configured));
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
			$this->logger->debug("Could not find account <{$accountId}> removing from jobs");
			$this->jobList->remove(self::class, $argument);
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			$this->logger->info('Ignoring preprocessing job for provisioned account as authentication on IMAP not possible');
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
