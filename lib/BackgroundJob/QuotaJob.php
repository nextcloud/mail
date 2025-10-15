<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;
use function sprintf;

class QuotaJob extends TimedJob {
	private IUserManager $userManager;
	private AccountService $accountService;
	private IMailManager $mailManager;
	private LoggerInterface $logger;
	private IJobList $jobList;
	private IManager $notificationManager;

	public function __construct(ITimeFactory $time,
		IUserManager $userManager,
		AccountService $accountService,
		IMailManager $mailManager,
		IManager $notificationManager,
		LoggerInterface $logger,
		IJobList $jobList) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->mailManager = $mailManager;

		$this->setInterval(60 * 60 * 24 * 7);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @return void
	 */
	#[\Override]
	protected function run($argument): void {
		$accountId = (int)$argument['accountId'];
		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			$this->logger->debug('No authentication on IMAP possible, skipping quota job');
			return;
		}

		$user = $this->userManager->get($account->getUserId());
		if ($user === null || !$user->isEnabled()) {
			$this->logger->debug(sprintf(
				'Account %d of user %s could not be found or was disabled, skipping quota query',
				$account->getId(),
				$account->getUserId()
			));
			return;
		}

		$quota = $this->mailManager->getQuota($account);
		if ($quota === null) {
			$this->logger->debug('Could not get quota information for account <' . $account->getEmail() . '>', ['app' => 'mail']);
			return;
		}
		$previous = $account->getMailAccount()->getQuotaPercentage();
		$account->calculateAndSetQuotaPercentage($quota);
		$this->accountService->update($account->getMailAccount());
		$current = $account->getQuotaPercentage();

		// Only notify if we've reached the rising edge
		if ($previous < $current && $previous <= 90 && $current > 90) {
			$this->logger->debug('New quota information for <' . $account->getEmail() . '> - previous: ' . $previous . ', current: ' . $current);
			$time = $this->time->getDateTime('now');
			$notification = $this->notificationManager->createNotification();
			$notification
				->setApp('mail')
				->setUser($account->getUserId())
				->setObject('quota', (string)$accountId)
				->setSubject('quota_depleted', [
					'id' => $accountId,
					'account_email' => $account->getEmail()
				])
				->setDateTime($time)
				->setMessage('percentage', [
					'id' => $accountId,
					'quota_percentage' => $current,
				]
				);
			$this->notificationManager->notify($notification);
		}
	}
}
