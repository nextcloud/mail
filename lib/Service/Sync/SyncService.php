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
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use Psr\Log\LoggerInterface;
use function array_diff;
use function array_map;

class SyncService {
	/** Same as the page size of the message list in the frontend */
	private const MAX_NEW_WITHOUT_KNOWN_IDS = 20;

	public function __construct(
		private ProtocolFactory $protocolFactory,
		private ImapToDbSynchronizer $synchronizer,
		private FilterStringParser $filterStringParser,
		private MessageMapper $messageMapper,
		private PreviewEnhancer $previewEnhancer,
		private LoggerInterface $logger,
		private MailboxSync $mailboxSync,
	) {
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function clearCache(Account $account, Mailbox $mailbox): void {
		$this->protocolFactory->messageConnector($account)->clearCache($account, $mailbox);
	}

	/**
	 * Run a (rather costly) sync to delete cached messages which are not present on IMAP anymore.
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function repairSync(Account $account, Mailbox $mailbox): void {
		$this->protocolFactory->messageConnector($account)->repairSync($account, $mailbox);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $criteria
	 * @param bool $partialOnly
	 * @param string|null $filter
	 *
	 * @param int[] $knownIds
	 *
	 * @return Response
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
		?string $filter = null,
	): Response {
		if ($partialOnly && !$mailbox->isCached()) {
			throw MailboxNotCachedException::from($mailbox);
		}

		$this->protocolFactory
			->mailboxConnector($account)
			->syncOne($account, $mailbox);

		$this->protocolFactory
			->messageConnector($account)
			->syncMailbox(
				$account,
				$mailbox,
				$this->logger,
				$criteria,
				$knownIds === null ? null : $this->messageMapper->findUidsForIds($mailbox, $knownIds),
				!$partialOnly,
			);

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
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $knownIds
	 * @param SearchQuery $query
	 *
	 * @return Response
	 * @todo does not work with text token search queries
	 *
	 */
	private function getDatabaseSyncChanges(Account $account,
		Mailbox $mailbox,
		array $knownIds,
		?int $lastMessageTimestamp,
		string $sortOrder,
		?SearchQuery $query): Response {
		$order = $sortOrder === IMailSearch::ORDER_OLDEST_FIRST ? IMailSearch::ORDER_OLDEST_FIRST : IMailSearch::ORDER_NEWEST_FIRST;
		if ($knownIds === []) {
			// The client shows none of these messages: an empty mailbox, or one account in a
			// combined list (such as the priority inbox) whose page is filled by other accounts.
			// Send at most one page, like the first page the client would load;
			// otherwise every message of the mailbox goes through the preview enhancer here.
			$newIds = $this->messageMapper->findIdsByQuery($mailbox, $query ?? new SearchQuery(), $order, self::MAX_NEW_WITHOUT_KNOWN_IDS);
		} else {
			$newIds = $this->messageMapper->findNewIds($mailbox, $knownIds, $lastMessageTimestamp, $sortOrder);
			if ($query !== null) {
				$newIds = $this->messageMapper->findIdsByQuery($mailbox, $query, $order, null, null, $newIds);
			}
		}
		$new = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $newIds);

		// TODO: $changed = $this->messageMapper->findChanged($account, $mailbox, $uids);
		if ($query !== null) {
			$changedIds = $this->messageMapper->findIdsByQuery($mailbox, $query, $order, null, null, $knownIds);
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
