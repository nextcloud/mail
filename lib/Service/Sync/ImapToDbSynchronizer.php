<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Sync;

use Horde_Imap_Client;
use Horde_Imap_Client_Base;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Ids;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\MailboxDoesNotSupportModSequencesException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\UidValidityChangedException;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Classification\NewMessagesClassifier;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_chunk;
use function array_diff;
use function array_filter;
use function array_map;
use function register_shutdown_function;
use function sort;
use function sprintf;

class ImapToDbSynchronizer {
	/** @var int */
	public const MAX_NEW_MESSAGES = 5000;
	public const PARTIAL_SYNC_CHUNK_SIZE = 500;

	/** @var IEventDispatcher */
	private $dispatcher;

	public function __construct(
		private DatabaseMessageMapper $dbMapper,
		private ProtocolFactory $protocolFactory,
		private ImapMessageMapper $imapMapper,
		private MailboxMapper $mailboxMapper,
		DatabaseMessageMapper $messageMapper,
		private Synchronizer $synchronizer,
		IEventDispatcher $dispatcher,
		private PerformanceLogger $performanceLogger,
		private LoggerInterface $logger,
		private MailManager $mailManager,
		private TagMapper $tagMapper,
		private NewMessagesClassifier $newMessagesClassifier,
	) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function syncAccount(Account $account,
		LoggerInterface $logger,
		bool $force = false,
		int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS): void {
		$rebuildThreads = false;
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		$snoozeMailboxId = $account->getMailAccount()->getSnoozeMailboxId();
		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		$trashRetentionDays = $account->getMailAccount()->getTrashRetentionDays();

		$client = $this->protocolFactory->imapClient($account);

		foreach ($this->mailboxMapper->findAll($account) as $mailbox) {
			$syncTrash = $trashMailboxId === $mailbox->getId() && $trashRetentionDays !== null;
			$syncSnooze = $snoozeMailboxId === $mailbox->getId();
			$syncSent = $sentMailboxId === $mailbox->getId() || $mailbox->isSpecialUse('sent');

			if (!$syncTrash && !$mailbox->isInbox() && !$syncSnooze && !$mailbox->getSyncInBackground() && !$syncSent) {
				$logger->debug("Skipping mailbox sync for {$mailbox->getId()}");
				continue;
			}
			$logger->debug("Syncing {$mailbox->getId()}");
			if ($this->sync(
				$account,
				$client,
				$mailbox,
				$logger,
				$criteria,
				null,
				$force,
				true
			)) {
				$rebuildThreads = true;
			}
		}

		$this->dispatcher->dispatchTyped(
			new SynchronizationEvent(
				$account,
				$logger,
				$rebuildThreads,
			)
		);
	}

	/**
	 * Clear all cached data of a mailbox
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function clearCache(Account $account,
		Mailbox $mailbox): void {
		$id = $account->getId() . ':' . $mailbox->getName();
		try {
			$this->mailboxMapper->lockForNewSync($mailbox);
			$this->mailboxMapper->lockForChangeSync($mailbox);
			$this->mailboxMapper->lockForVanishedSync($mailbox);

			$this->resetCache($account, $mailbox);
		} catch (Throwable $e) {
			throw new ServiceException("Could not clear mailbox cache for $id: " . $e->getMessage(), 0, $e);
		} finally {
			$this->mailboxMapper->unlockFromNewSync($mailbox);
			$this->mailboxMapper->unlockFromChangedSync($mailbox);
			$this->mailboxMapper->unlockFromVanishedSync($mailbox);
		}
	}

	/**
	 * Wipe all cached messages of a mailbox from the database
	 *
	 * Warning: the caller has to ensure the mailbox is locked
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 */
	private function resetCache(Account $account, Mailbox $mailbox): void {
		$id = $account->getId() . ':' . $mailbox->getName();
		$this->dbMapper->deleteAll($mailbox);
		$this->logger->debug("All messages of $id cleared");
		$mailbox->setSyncNewToken(null);
		$mailbox->setSyncChangedToken(null);
		$mailbox->setSyncVanishedToken(null);
		$this->mailboxMapper->update($mailbox);
	}

	/**
	 * @throws ClientException
	 * @throws MailboxNotCachedException
	 * @throws ServiceException
	 * @return bool whether to rebuild threads or not
	 */
	public function sync(Account $account,
		Horde_Imap_Client_Base $client,
		Mailbox $mailbox,
		LoggerInterface $logger,
		int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
		?array $knownUids = null,
		bool $force = false,
		bool $batchSync = false): bool {

		$rebuildThreads = true;
		if ($mailbox->getSelectable() === false) {
			return $rebuildThreads;
		}

		$client->login(); // Need to login before fetching capabilities.

		// There is no partial sync when using QRESYNC. As per RFC the client will always pull
		// all changes. This is a cheap operation when using QRESYNC as the server keeps track
		// of a client's state through the sync token. We could just update the sync tokens and
		// call it a day because Horde caches unrelated/unrequested changes until the next
		// operation. However, our cache is not reliable as some instance might use APCu which
		// isn't shared between cron and web requests.
		$hasQresync = false;
		if ($client->capability->isEnabled('QRESYNC')) {
			$this->logger->debug('Forcing full sync due to QRESYNC');
			$hasQresync = true;
			$criteria |= Horde_Imap_Client::SYNC_NEWMSGSUIDS
				| Horde_Imap_Client::SYNC_FLAGSUIDS
				| Horde_Imap_Client::SYNC_VANISHEDUIDS;
		}

		if ($force || ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS)) {
			$logger->debug("Locking mailbox {$mailbox->getId()} for new messages sync");
			$this->mailboxMapper->lockForNewSync($mailbox);
		}
		if ($force || ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS)) {
			$logger->debug("Locking mailbox {$mailbox->getId()} for changed messages sync");
			$this->mailboxMapper->lockForChangeSync($mailbox);
		}
		if ($force || ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS)) {
			$logger->debug("Locking mailbox {$mailbox->getId()} for vanished messages sync");
			$this->mailboxMapper->lockForVanishedSync($mailbox);
		}

		register_shutdown_function(function () use ($force, $criteria, $logger, $mailbox) {
			$this->unlockMailbox($force, $criteria, $logger, $mailbox);
		});

		try {
			if ($force
				|| $mailbox->getSyncNewToken() === null
				|| $mailbox->getSyncChangedToken() === null
				|| $mailbox->getSyncVanishedToken() === null) {
				$logger->debug("Running initial sync for {$mailbox->getId()}");
				$this->runInitialSync($client, $account, $mailbox, $logger);
			} else {
				try {
					$logger->debug("Running partial sync for {$mailbox->getId()} with criteria $criteria");
					// Only rebuild threads if there were new or vanished messages
					$rebuildThreads = $this->runPartialSync($client, $account, $mailbox, $logger, $hasQresync, $criteria, $knownUids);
				} catch (UidValidityChangedException $e) {
					$logger->warning("Mailbox UID validity changed. Wiping cache and performing full sync for {$mailbox->getId()}");
					$this->resetCache($account, $mailbox);
					$logger->debug("Running initial sync for {$mailbox->getId()} after cache reset");
					$this->runInitialSync($client, $account, $mailbox, $logger);
				} catch (MailboxDoesNotSupportModSequencesException $e) {
					$logger->warning("Mailbox does not support mod-sequences error occurred. Wiping cache and performing full sync for {$mailbox->getId()}", [
						'exception' => $e,
					]);
					$this->resetCache($account, $mailbox);
					$logger->debug("Running initial sync for {$mailbox->getId()} after cache reset - no mod-sequences error");
					$this->runInitialSync($client, $account, $mailbox, $logger);
				}
			}
		} catch (ServiceException $e) {
			// Just rethrow, don't wrap into another exception
			throw $e;
		} catch (Throwable $e) {
			throw new ServiceException("Sync failed for {$account->getId()}:{$mailbox->getName()}: {$e->getMessage()}", 0, $e);
		} finally {
			$this->unlockMailbox($force, $criteria, $logger, $mailbox);
		}

		if (!$batchSync) {
			$this->dispatcher->dispatchTyped(
				new SynchronizationEvent(
					$account,
					$this->logger,
					$rebuildThreads,
				)
			);
		}

		return $rebuildThreads;
	}

	private function unlockMailbox(bool $force, int $criteria, LoggerInterface $logger, Mailbox $mailbox): void {
		if (($force || ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS))
			&& $mailbox->getSyncVanishedLock() !== null) {
			$logger->debug("Unlocking mailbox {$mailbox->getId()} from vanished messages sync");
			$this->mailboxMapper->unlockFromVanishedSync($mailbox);
		}
		if (($force || ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS))
			&& $mailbox->getSyncChangedLock() !== null) {
			$logger->debug("Unlocking mailbox {$mailbox->getId()} from changed messages sync");
			$this->mailboxMapper->unlockFromChangedSync($mailbox);
		}
		if (($force || ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS))
			&& $mailbox->getSyncNewLock() !== null) {
			$logger->debug("Unlocking mailbox {$mailbox->getId()} from new messages sync");
			$this->mailboxMapper->unlockFromNewSync($mailbox);
		}
	}

	/**
	 * @throws ServiceException
	 * @throws IncompleteSyncException
	 */
	private function runInitialSync(
		Horde_Imap_Client_Base $client,
		Account $account,
		Mailbox $mailbox,
		LoggerInterface $logger): void {
		$perf = $this->performanceLogger->startWithLogger(
			"Initial sync {$account->getId()}:{$mailbox->getName()}",
			$logger
		);

		// Cache-enabled client for HIGHESTMODSEQ (commit 7980d9e40), read before fetching for mail arriving meanwhile
		$syncToken = $client->getSyncToken($mailbox->getName());

		// Use a no-cache client for findAll. Horde accumulates cache state on
		// every iteration of the findAll loop, causing a memory leak on large
		// mailboxes (commit e50c214ff).
		$noCacheClient = $this->protocolFactory->imapClient($account, false);
		$highestKnownUid = $this->dbMapper->findHighestUid($mailbox);
		try {
			$imapMessages = $this->imapMapper->findAll(
				$noCacheClient,
				$mailbox->getName(),
				self::MAX_NEW_MESSAGES,
				$highestKnownUid ?? 0,
				$logger,
				$perf,
				$account->getUserId(),
			);
			$perf->step(sprintf('fetch %d messages from IMAP', count($imapMessages)));
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Can not get messages from mailbox ' . $mailbox->getName() . ': ' . $e->getMessage(), 0, $e);
		}

		foreach (array_chunk($imapMessages['messages'], 500) as $chunk) {
			$messages = array_map(static fn (IMAPMessage $imapMessage) => $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount()), $chunk);
			$this->dbMapper->insertBulk($account, ...$messages);
			$perf->step(sprintf('persist %d messages in database', count($chunk)));
			// Free the memory
			unset($messages);
		}

		if (!$imapMessages['all']) {
			// We might need more attempts to fill the cache
			$loggingMailboxId = $account->getId() . ':' . $mailbox->getName();
			$total = $imapMessages['total'];
			$cached = count($this->dbMapper->findAllUids($mailbox));
			$perf->step('find number of cached UIDs');

			$perf->end();
			throw new IncompleteSyncException("Initial sync is not complete for $loggingMailboxId ($cached of $total messages cached).");
		}

		$mailbox->setSyncNewToken($syncToken);
		$mailbox->setSyncChangedToken($syncToken);
		$mailbox->setSyncVanishedToken($syncToken);
		$this->mailboxMapper->update($mailbox);

		$perf->end();
	}

	/**
	 * @param int[] $knownUids
	 *
	 * @throws ServiceException
	 * @throws UidValidityChangedException
	 * @return bool whether there are new or vanished messages
	 */
	private function runPartialSync(
		Horde_Imap_Client_Base $client,
		Account $account,
		Mailbox $mailbox,
		LoggerInterface $logger,
		bool $hasQresync,
		int $criteria,
		?array $knownUids = null): bool {
		$newOrVanished = false;
		$perf = $this->performanceLogger->startWithLogger(
			"partial sync {$account->getId()}:{$mailbox->getName()}",
			$logger
		);

		$uids = $knownUids ?? $this->dbMapper->findAllUids($mailbox);
		$perf->step('get all known UIDs');

		$requestId = base64_encode(random_bytes(16));
		$syncsChanges = ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) !== 0;
		$permflagsEnabled = $syncsChanges && $this->mailManager->isPermflagsEnabled($account, $mailbox);

		// Read from the selected mailbox before asking IMAP for changes, for mail arriving during the run
		$client->openMailbox($mailbox->getName());
		$syncToken = $client->getSyncToken($mailbox->getName());

		$newUids = [];
		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			[$newUids] = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncNewToken(),
					$uids
				),
				$hasQresync,
				$logger,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS,
			);
			$perf->step('get new message UIDs via Horde');
			// Also true for messages that a failed run already stored, so their threads are built
			$newOrVanished = $newUids !== [];

			$highestKnownUid = $this->dbMapper->findHighestUid($mailbox);
			if ($highestKnownUid !== null) {
				// Already stored: mail that arrived while the previous run read its token, or chunks of a failed run
				$newUids = array_filter($newUids, static fn (int $uid) => $uid > $highestKnownUid);
			}
			// Ascending chunks keep the filter above valid when a later chunk fails
			sort($newUids);

			$importantTag = null;
			try {
				$importantTag = $this->tagMapper->getTagByImapLabel(Tag::LABEL_IMPORTANT, $account->getUserId());
			} catch (DoesNotExistException $e) {
				$this->logger->error('Could not find important tag for ' . $account->getUserId() . ' ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}

			foreach (array_chunk($newUids, self::PARTIAL_SYNC_CHUNK_SIZE) as $chunk) {
				$dbMessages = $this->fetchDbMessages($client, $account, $mailbox, $chunk);
				if ($dbMessages === []) {
					continue;
				}

				$this->dbMapper->insertBulk($account, ...$dbMessages);
				if ($hasQresync && $syncsChanges) {
					// Removes tags that another copy with the same Message-ID left behind
					$this->dbMapper->updateBulk($account, $permflagsEnabled, ...$dbMessages);
				}

				if ($importantTag) {
					$classified = $this->newMessagesClassifier->classifyNewMessages(
						$dbMessages,
						$mailbox,
						$account,
						$importantTag,
					);
					if ($classified) {
						$perf->step('classified a chunk of new messages');
					} else {
						$perf->step('skipped classification');
					}
				}

				$this->dispatcher->dispatch(
					NewMessagesSynchronized::class,
					new NewMessagesSynchronized($account, $mailbox, $dbMessages)
				);
				$perf->step('emitted NewMessagesSynchronized event');
			}
			$perf->step('persist new messages');
		}
		if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
			[, $changedUids] = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncChangedToken(),
					$uids
				),
				$hasQresync,
				$logger,
				Horde_Imap_Client::SYNC_FLAGSUIDS,
			);
			$perf->step('get changed message UIDs via Horde');
			// QRESYNC can report new messages as changed too. They were just stored with their current flags.
			$changedUids = array_diff($changedUids, $newUids);

			foreach (array_chunk($changedUids, self::PARTIAL_SYNC_CHUNK_SIZE) as $chunk) {
				$dbMessages = $this->fetchDbMessages($client, $account, $mailbox, $chunk);
				if ($dbMessages === []) {
					continue;
				}
				$this->dbMapper->updateBulk($account, $permflagsEnabled, ...$dbMessages);
			}
			$perf->step('persist changed messages');
		}
		if ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS) {
			[, , $vanishedUids] = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncVanishedToken(),
					$uids
				),
				$hasQresync,
				$logger,
				Horde_Imap_Client::SYNC_VANISHEDUIDS,
			);
			$perf->step('get vanished message UIDs via Horde');

			foreach (array_chunk($vanishedUids, self::PARTIAL_SYNC_CHUNK_SIZE) as $chunk) {
				$this->dbMapper->deleteByUid($mailbox, ...$chunk);
			}
			$perf->step('delete vanished messages');

			$newOrVanished = $newOrVanished || $vanishedUids !== [];
		}

		// Unlocking saves the mailbox, so a failed run must not have advanced any token
		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			$mailbox->setSyncNewToken($syncToken);
		}
		// A provided list of UIDs may hide changes, so the change and vanish tokens wait for a full sync
		if ($knownUids === null && $syncsChanges) {
			$mailbox->setSyncChangedToken($syncToken);
		}
		if ($knownUids === null && ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS)) {
			$mailbox->setSyncVanishedToken($syncToken);
		}
		$this->mailboxMapper->update($mailbox);
		$perf->end();

		return $newOrVanished;
	}

	/**
	 * @param int[] $uids
	 * @return Message[]
	 */
	private function fetchDbMessages(Horde_Imap_Client_Base $client, Account $account, Mailbox $mailbox, array $uids): array {
		return array_map(
			static fn (IMAPMessage $imapMessage) => $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount()),
			$this->imapMapper->findByIds($client, $mailbox->getName(), $uids, $account->getUserId()),
		);
	}

	/**
	 * Run a (rather costly) sync to delete cached messages which are not present on IMAP anymore.
	 *
	 * @throws MailboxLockedException
	 * @throws ServiceException
	 */
	public function repairSync(
		Account $account,
		Mailbox $mailbox,
		LoggerInterface $logger,
	): void {
		$this->mailboxMapper->lockForVanishedSync($mailbox);

		$perf = $this->performanceLogger->startWithLogger(
			"Repair sync for {$account->getId()}:{$mailbox->getName()}",
			$logger,
		);

		// Need to use a client without a cache here (to disable QRESYNC entirely)
		$client = $this->protocolFactory->imapClient($account, false);
		try {
			// The pooled connection may still have the mailbox selected with a stale view
			$client->noop();
			$knownUids = $this->dbMapper->findAllUids($mailbox);
			$hordeMailbox = new \Horde_Imap_Client_Mailbox($mailbox->getName());
			$phantomVanishedUids = $client->vanished($hordeMailbox, 0, [
				'ids' => new Horde_Imap_Client_Ids($knownUids),
			])->ids;
			if (count($phantomVanishedUids) > 0) {
				$this->dbMapper->deleteByUid($mailbox, ...$phantomVanishedUids);
			}
		} catch (Throwable $e) {
			$message = sprintf(
				'Repair sync failed for %d:%s: %s',
				$account->getId(),
				$mailbox->getName(),
				$e->getMessage(),
			);
			throw new ServiceException($message, 0, $e);
		} finally {
			$this->mailboxMapper->unlockFromVanishedSync($mailbox);
		}

		$perf->end();
	}
}
