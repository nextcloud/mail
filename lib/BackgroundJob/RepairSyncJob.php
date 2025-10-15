<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class RepairSyncJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private SyncService $syncService,
		private AccountService $accountService,
		private IUserManager $userManager,
		private MailboxMapper $mailboxMapper,
		private IJobList $jobList,
		private LoggerInterface $logger,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct($time);

		$this->setInterval(3600 * 24 * 7);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

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
			$this->logger->debug('No authentication on IMAP possible, skipping background sync job');
			return;
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

		$rebuildThreads = false;
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		$snoozeMailboxId = $account->getMailAccount()->getSnoozeMailboxId();
		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		$junkMailboxId = $account->getMailAccount()->getJunkMailboxId();
		foreach ($this->mailboxMapper->findAll($account) as $mailbox) {
			$isExcluded = [
				$trashMailboxId === $mailbox->getId(),
				$snoozeMailboxId === $mailbox->getId(),
				$sentMailboxId === $mailbox->getId(),
				$junkMailboxId === $mailbox->getId(),
			];
			if (in_array(true, $isExcluded, true)) {
				continue;
			}

			if ($this->syncService->repairSync($account, $mailbox) > 0) {
				$rebuildThreads = true;
			}
		}

		$this->dispatcher->dispatchTyped(
			new SynchronizationEvent($account, $this->logger, $rebuildThreads),
		);
	}
}
