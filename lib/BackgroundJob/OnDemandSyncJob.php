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
 * Async on-demand sync triggered when a user scrolls past cached messages.
 *
 * Instead of blocking the HTTP request with a synchronous IMAP fetch,
 * MailSearch schedules this job. The frontend retries after a short delay
 * and picks up the newly synced messages from the local DB.
 */
class OnDemandSyncJob extends QueuedJob {
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
		$cursorTimestamp = (int)($argument['cursorTimestamp'] ?? 0);

		if ($accountId === 0 || $mailboxId === 0 || $cursorTimestamp === 0) {
			return;
		}

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			return;
		}

		if (!$account->getMailAccount()->canAuthenticateImap()) {
			return;
		}

		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException $e) {
			return;
		}

		try {
			$synced = $this->syncService->syncOlderMessages(
				$account,
				$mailbox,
				$cursorTimestamp,
			);
			$this->logger->debug("OnDemandSyncJob: synced $synced messages for account $accountId mailbox $mailboxId");
		} catch (\Throwable $e) {
			$this->logger->warning('OnDemandSyncJob failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}
}
