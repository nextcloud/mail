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
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use function array_diff;
use function array_map;

class SyncService {

	private IMAPClientFactory $clientFactory;

	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var FilterStringParser */
	private $filterStringParser;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var PreviewEnhancer */
	private $previewEnhancer;

	/** @var LoggerInterface */
	private $logger;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var IAppConfig */
	private $config;

	public function __construct(
		IMAPClientFactory $clientFactory,
		ImapToDbSynchronizer $synchronizer,
		FilterStringParser $filterStringParser,
		MessageMapper $messageMapper,
		PreviewEnhancer $previewEnhancer,
		LoggerInterface $logger,
		MailboxSync $mailboxSync,
		IAppConfig $config,
	) {
		$this->clientFactory = $clientFactory;
		$this->synchronizer = $synchronizer;
		$this->filterStringParser = $filterStringParser;
		$this->messageMapper = $messageMapper;
		$this->previewEnhancer = $previewEnhancer;
		$this->logger = $logger;
		$this->mailboxSync = $mailboxSync;
		$this->config = $config;
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
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

		$threadingEnabled = $this->config->getValueString('mail', 'layout-message-view', 'threaded') === 'threaded';
		$client->logout();

		$query = $filter === null ? null : $this->filterStringParser->parse($filter);
		return $this->getDatabaseSyncChanges(
			$account,
			$mailbox,
			$knownIds ?? [],
			$lastMessageTimestamp,
			$sortOrder,
			$query,
			$threadingEnabled
		);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $knownIds
	 * @param SearchQuery $query
	 * @param bool $threadingEnabled
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
		?SearchQuery $query,
		bool $threadingEnabled): Response {
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

		$new = $this->messageMapper->findMessageListsByMailboxAndIds($account, $mailbox, $account->getUserId(), $newIds, $sortOrder, $threadingEnabled);

		$newMessages = [];
		foreach ($new as $messageList) {
			$newMessages[] = $this->previewEnhancer->process($account, $mailbox, $messageList);
		}

		// TODO: $changed = $this->messageMapper->findChanged($account, $mailbox, $uids);
		if ($query !== null) {
			$changedUids = $this->messageMapper->findUidsForIds($mailbox, $knownIds);
			$changedIds = $this->messageMapper->findIdsByQuery($mailbox, $query, $order, null, $changedUids);
		} else {
			$changedIds = $knownIds;
		}
		$changed = $this->messageMapper->findByMailboxAndIds($mailbox, $account->getUserId(), $changedIds, $sortOrder);

		$stillKnownIds = array_map(static fn (Message $msg) => $msg->getId(), $changed);
		$vanished = array_values(array_diff($knownIds, $stillKnownIds));

		return new Response(
			$newMessages,
			$changed,
			$vanished,
			$mailbox->getStats()
		);
	}
}
