<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Background job to deep-sync older messages for a single mailbox.
 *
 * This is a QueuedJob (runs once per scheduling) instead of being inlined
 * in the per-account SyncJob TimedJob. This prevents deep-sync from blocking
 * the main cron cycle when there are thousands of accounts.
 *
 * Nextcloud's cron runner processes QueuedJobs round-robin, so 10K jobs are
 * spread across multiple cron invocations (~1000 jobs per 5-min cycle).
 */
class DeepSyncJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private ImapToDbSynchronizer $syncService,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	#[\Override]
	protected function run($argument): void {
		$accountId = (int)($argument['accountId'] ?? 0);
		$mailboxId = (int)($argument['mailboxId'] ?? 0);

		if ($accountId === 0 || $mailboxId === 0) {
			return;
		}

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug("DeepSyncJob: account $accountId not found, skipping");
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			return;
		}

		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug("DeepSyncJob: mailbox $mailboxId not found, skipping");
			return;
		}

		try {
			$this->syncService->syncOlderMessagesBackground(
				$account,
				$mailbox,
				$this->logger,
			);
		} catch (\Throwable $e) {
			$this->logger->warning("DeepSyncJob failed for account $accountId mailbox $mailboxId: " . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
