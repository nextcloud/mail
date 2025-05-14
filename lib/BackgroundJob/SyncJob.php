<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use Horde_Imap_Client_Exception;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;
use function max;
use function sprintf;

class SyncJob extends TimedJob {
	private const DEFAULT_SYNC_INTERVAL = 3600;

	private IUserManager $userManager;
	private AccountService $accountService;
	private ImapToDbSynchronizer $syncService;
	private MailboxSync $mailboxSync;
	private LoggerInterface $logger;
	private IJobList $jobList;
	private readonly bool $forcedSyncInterval;

	public function __construct(
		ITimeFactory $time,
		IUserManager $userManager,
		AccountService $accountService,
		MailboxSync $mailboxSync,
		ImapToDbSynchronizer $syncService,
		LoggerInterface $logger,
		IJobList $jobList,
		private readonly IConfig $config,
	) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->syncService = $syncService;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;
		$this->jobList = $jobList;

		$configuredSyncInterval = $config->getSystemValueInt('app.mail.background-sync-interval');
		if ($configuredSyncInterval > 0) {
			$this->forcedSyncInterval = true;
		} else {
			$this->forcedSyncInterval = false;
			$configuredSyncInterval = self::DEFAULT_SYNC_INTERVAL;
		}

		$this->setInterval(max(5 * 60, $configuredSyncInterval));
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
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
			$this->logger->debug('No authentication on IMAP possible, skipping background sync job');
			return;
		}

		// If an admin configured a custom sync interval, always abide by it
		if (!$this->forcedSyncInterval) {
			$now = $this->time->getTime();
			$heartbeat = (int)$this->config->getUserValue(
				$account->getUserId(),
				Application::APP_ID,
				'ui-heartbeat',
				$now + 1, // Force negative value for $lastUsed in case of no heartbeat
			);
			$lastUsed = $now - $heartbeat;
			if ($lastUsed > 3 * 24 * 3600) {
				// User did not open the app in more than three days -> defer sync
				$this->setInterval(6 * 3600);
			} elseif ($lastUsed > 24 * 3600) {
				// User opened the app at least once within the last three days -> default sync
				$this->setInterval(self::DEFAULT_SYNC_INTERVAL);
			} elseif ($lastUsed > 0) {
				// User opened the app at least once within the last 24 hours -> sync more often
				$this->setInterval(15 * 60);
			} else {
				// Default to the hourly interval in case there is no heartbeat
				$this->setInterval(self::DEFAULT_SYNC_INTERVAL);
			}
		}

		$user = $this->userManager->get($account->getUserId());
		if ($user === null || !$user->isEnabled()) {
			$this->logger->debug(sprintf(
				'Account %d of user %s could not be found or was disabled, skipping background sync',
				$account->getId(),
				$account->getUserId()
			));
			return;
		}

		try {
			$this->mailboxSync->sync($account, $this->logger, true);
			$this->syncService->syncAccount($account, $this->logger);
		} catch (IncompleteSyncException $e) {
			$this->logger->warning($e->getMessage(), [
				'exception' => $e,
			]);
		} catch (Throwable $e) {
			if ($e instanceof ServiceException
				&& $e->getPrevious() instanceof Horde_Imap_Client_Exception
				&& $e->getPrevious()->getCode() === Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED) {
				$this->logger->info('Cron mail sync authentication failed for account {accountId}', [
					'accountId' => $accountId,
					'exception' => $e,
				]);
			} else {
				$this->logger->error('Cron mail sync failed for account {accountId}', [
					'accountId' => $accountId,
					'exception' => $e,
				]);
			}
		}
	}
}
