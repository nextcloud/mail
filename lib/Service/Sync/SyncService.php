<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\SearchQuery;
use function array_diff;
use function array_map;

class SyncService {

	public function __construct(
		private readonly IMAPClientFactory $clientFactory,
		private readonly \OCA\Mail\Service\Sync\ImapToDbSynchronizer $synchronizer,
		private readonly \OCA\Mail\Service\Search\FilterStringParser $filterStringParser,
		private readonly \OCA\Mail\Db\MessageMapper $messageMapper,
		private readonly \OCA\Mail\IMAP\PreviewEnhancer $previewEnhancer,
		private readonly \Psr\Log\LoggerInterface $logger,
		private readonly \OCA\Mail\IMAP\MailboxSync $mailboxSync
	) {
	}

	/**
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function clearCache(Account $account,
		Mailbox $mailbox): void {
		$this->synchronizer->clearCache($account, $mailbox);
	}

	/**
	 * Run a (rather costly) sync to delete cached messages which are not present on IMAP anymore.
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function repairSync(Account $account, Mailbox $mailbox): void {
		$this->synchronizer->repairSync($account, $mailbox, $this->logger);
	}

	/**
	 * @param int[] $knownIds
	 *
	 * @throws ClientException
	 * @throws MailboxNotCachedException
	 * @throws ServiceException
	 */
	public function syncMailbox(Account $account,
		Mailbox $mailbox,
		int $criteria,
		bool $partialOnly,
		?int $lastMessageTimestamp,
		?array $knownIds = null,
		string $sortOrder = IMailSearch::ORDER_NEWEST_FIRST,
		?string $filter = null): Response {
		if ($partialOnly && !$mailbox->isCached()) {
			throw MailboxNotCachedException::from($mailbox);
		}

		$client = $this->clientFactory->getClient($account);

		$this->synchronizer->sync(
			$account,
			$client,
			$mailbox,
			$this->logger,
			$criteria,
			$knownIds === null ? null : $this->messageMapper->findUidsForIds($mailbox, $knownIds),
			!$partialOnly
		);

		$this->mailboxSync->syncStats($client, $mailbox);

		$client->logout();

		$query = $filter === null ? null : $this->filterStringParser->parse($filter);
		return $this->getDatabaseSyncChanges(
			$account,
			$mailbox,
			$knownIds ?? [],
			$lastMessageTimestamp,
			$sortOrder,
			$query
		);
	}

	/**
	 * @param int[] $knownIds
	 * @param SearchQuery $query
	 * @todo does not work with text token search queries
	 *
	 */
	private function getDatabaseSyncChanges(Account $account,
		Mailbox $mailbox,
		array $knownIds,
		?int $lastMessageTimestamp,
		string $sortOrder,
		?SearchQuery $query): Response {
		if ($knownIds === []) {
			$newIds = $this->messageMapper->findAllIds($mailbox);
		} else {
			$newIds = $this->messageMapper->findNewIds($mailbox, $knownIds, $lastMessageTimestamp, $sortOrder);
		}
		$order = $sortOrder === 'oldest' ? IMailSearch::ORDER_OLDEST_FIRST : IMailSearch::ORDER_NEWEST_FIRST;
		if ($query !== null) {
			// Filter new messages to those that also match the current filter
			$newUids = $this->messageMapper->findUidsForIds($mailbox, $newIds);
			$newIds = $this->messageMapper->findIdsByQuery($mailbox, $query, $order, null, $newUids);
		}
		$new = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $newIds);

		// TODO: $changed = $this->messageMapper->findChanged($account, $mailbox, $uids);
		if ($query !== null) {
			$changedUids = $this->messageMapper->findUidsForIds($mailbox, $knownIds);
			$changedIds = $this->messageMapper->findIdsByQuery($mailbox, $query, $order, null, $changedUids);
		} else {
			$changedIds = $knownIds;
		}
		$changed = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $changedIds);

		$stillKnownIds = array_map(static fn (Message $msg) => $msg->getId(), $changed);
		$vanished = array_values(array_diff($knownIds, $stillKnownIds));

		return new Response(
			$this->previewEnhancer->process($account, $mailbox, $new),
			$changed,
			$vanished,
			$mailbox->getStats()
		);
	}
}
