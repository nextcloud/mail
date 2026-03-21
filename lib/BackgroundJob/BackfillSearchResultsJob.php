<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Backfills search result messages that exist on IMAP but not in the local DB.
 *
 * When a user searches and the IMAP server returns UIDs that are outside the
 * local sync window, this job fetches their metadata from IMAP and inserts
 * them into the local DB. The next search will then return complete results.
 */
class BackfillSearchResultsJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $clientFactory,
		private ImapMessageMapper $imapMapper,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	#[\Override]
	protected function run($argument): void {
		$accountId = (int)($argument['accountId'] ?? 0);
		$mailboxId = (int)($argument['mailboxId'] ?? 0);
		$uids = $argument['uids'] ?? [];

		if ($accountId === 0 || $mailboxId === 0 || $uids === []) {
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

		// Re-check which UIDs are still missing (some may have been synced since job was queued)
		$stillMissing = $this->messageMapper->findMissingUids($mailbox, $uids);
		if ($stillMissing === []) {
			$this->logger->debug("BackfillSearchResultsJob: all UIDs already in DB for mailbox $mailboxId");
			return;
		}

		// Cap to 200 UIDs per job to limit IMAP load
		$stillMissing = array_slice($stillMissing, 0, 200);

		$client = $this->clientFactory->getClient($account, false);
		try {
			$imapMessages = $this->imapMapper->findByIds(
				$client,
				$mailbox->getName(),
				$stillMissing,
				$account->getUserId(),
			);

			if (empty($imapMessages)) {
				$this->logger->debug("BackfillSearchResultsJob: no messages found on IMAP for mailbox $mailboxId");
				return;
			}

			$dbMessages = array_map(
				static function ($imapMessage) use ($mailbox, $account) {
					$msg = $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount());
					$msg->setStructureAnalyzed(true);
					return $msg;
				},
				$imapMessages
			);

			$inserted = $this->messageMapper->insertBulkIgnore($account, ...$dbMessages);
			$this->logger->debug("BackfillSearchResultsJob: inserted $inserted messages for mailbox $mailboxId");
		} catch (\Throwable $e) {
			$this->logger->warning("BackfillSearchResultsJob failed: " . $e->getMessage(), [
				'exception' => $e,
			]);
		} finally {
			$client->logout();
		}
	}
}
