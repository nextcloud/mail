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
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\MailboxDoesNotSupportModSequencesException;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\UidValidityChangedException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Support\PerformanceLogger;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_chunk;
use function array_filter;
use function array_map;
use function sprintf;

class ImapToDbSynchronizer {
	/** @var int */
	public const MAX_NEW_MESSAGES = 5000;

	/** @var DatabaseMessageMapper */
	private $dbMapper;

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var ImapMessageMapper */
	private $imapMapper;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var DatabaseMessageMapper */
	private $messageMapper;

	/** @var Synchronizer */
	private $synchronizer;

	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var PerformanceLogger */
	private $performanceLogger;

	/** @var LoggerInterface */
	private $logger;

	/** @var IMailManager */
	private $mailManager;

	public function __construct(DatabaseMessageMapper $dbMapper,
		IMAPClientFactory $clientFactory,
		ImapMessageMapper $imapMapper,
		MailboxMapper $mailboxMapper,
		DatabaseMessageMapper $messageMapper,
		Synchronizer $synchronizer,
		IEventDispatcher $dispatcher,
		PerformanceLogger $performanceLogger,
		LoggerInterface $logger,
		IMailManager $mailManager) {
		$this->dbMapper = $dbMapper;
		$this->clientFactory = $clientFactory;
		$this->imapMapper = $imapMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->synchronizer = $synchronizer;
		$this->dispatcher = $dispatcher;
		$this->performanceLogger = $performanceLogger;
		$this->logger = $logger;
		$this->mailManager = $mailManager;
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
		foreach ($this->mailboxMapper->findAll($account) as $mailbox) {
			$syncTrash = $trashMailboxId === $mailbox->getId() && $trashRetentionDays !== null;
			$syncSnooze = $snoozeMailboxId === $mailbox->getId();
			$syncSent = $sentMailboxId === $mailbox->getId() || $mailbox->isSpecialUse('sent');

			if (!$syncTrash && !$mailbox->isInbox() && !$syncSnooze && !$mailbox->getSyncInBackground() && !$syncSent) {
				$logger->debug('Skipping mailbox sync for ' . $mailbox->getId());
				continue;
			}
			$logger->debug('Syncing ' . $mailbox->getId());
			if ($this->sync(
				$account,
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
		$this->messageMapper->deleteAll($mailbox);
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

		$client = $this->clientFactory->getClient($account);
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
			$logger->debug('Locking mailbox ' . $mailbox->getId() . ' for new messages sync');
			$this->mailboxMapper->lockForNewSync($mailbox);
		}
		if ($force || ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS)) {
			$logger->debug('Locking mailbox ' . $mailbox->getId() . ' for changed messages sync');
			$this->mailboxMapper->lockForChangeSync($mailbox);
		}
		if ($force || ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS)) {
			$logger->debug('Locking mailbox ' . $mailbox->getId() . ' for vanished messages sync');
			$this->mailboxMapper->lockForVanishedSync($mailbox);
		}

		try {
			if ($force
				|| $mailbox->getSyncNewToken() === null
				|| $mailbox->getSyncChangedToken() === null
				|| $mailbox->getSyncVanishedToken() === null) {
				$logger->debug('Running initial sync for ' . $mailbox->getId());
				$this->runInitialSync($client, $account, $mailbox, $logger);
			} else {
				try {
					$logger->debug('Running partial sync for ' . $mailbox->getId());
					// Only rebuild threads if there were new or vanished messages
					$rebuildThreads = $this->runPartialSync($client, $account, $mailbox, $logger, $hasQresync, $criteria, $knownUids);
				} catch (UidValidityChangedException $e) {
					$logger->warning('Mailbox UID validity changed. Wiping cache and performing full sync for ' . $mailbox->getId());
					$this->resetCache($account, $mailbox);
					$logger->debug('Running initial sync for ' . $mailbox->getId() . ' after cache reset');
					$this->runInitialSync($client, $account, $mailbox, $logger);
				} catch (MailboxDoesNotSupportModSequencesException $e) {
					$logger->warning('Mailbox does not support mod-sequences error occured. Wiping cache and performing full sync for ' . $mailbox->getId(), [
						'exception' => $e,
					]);
					$this->resetCache($account, $mailbox);
					$logger->debug('Running initial sync for ' . $mailbox->getId() . ' after cache reset - no mod-sequences error');
					$this->runInitialSync($client, $account, $mailbox, $logger);
				}
			}
		} catch (ServiceException $e) {
			// Just rethrow, don't wrap into another exception
			throw $e;
		} catch (Throwable $e) {
			throw new ServiceException('Sync failed for ' . $account->getId() . ':' . $mailbox->getName() . ': ' . $e->getMessage(), 0, $e);
		} finally {
			if ($force || ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS)) {
				$logger->debug('Unlocking mailbox ' . $mailbox->getId() . ' from vanished messages sync');
				$this->mailboxMapper->unlockFromVanishedSync($mailbox);
			}
			if ($force || ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS)) {
				$logger->debug('Unlocking mailbox ' . $mailbox->getId() . ' from changed messages sync');
				$this->mailboxMapper->unlockFromChangedSync($mailbox);
			}
			if ($force || ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS)) {
				$logger->debug('Unlocking mailbox ' . $mailbox->getId() . ' from new messages sync');
				$this->mailboxMapper->unlockFromNewSync($mailbox);
			}

			$client->logout();
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

	/**
	 * @throws ServiceException
	 * @throws IncompleteSyncException
	 */
	private function runInitialSync(
		Horde_Imap_Client_Base $client,
		Account $account,
		Mailbox $mailbox,
		LoggerInterface  $logger): void {
		$perf = $this->performanceLogger->startWithLogger(
			'Initial sync ' . $account->getId() . ':' . $mailbox->getName(),
			$logger
		);

		// Need a client without a cache
		$client->logout();
		$client = $this->clientFactory->getClient($account, false);

		$highestKnownUid = $this->dbMapper->findHighestUid($mailbox);
		try {
			$imapMessages = $this->imapMapper->findAll(
				$client,
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
			$messages = array_map(static function (IMAPMessage $imapMessage) use ($mailbox, $account) {
				return $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount());
			}, $chunk);
			$this->dbMapper->insertBulk($account, ...$messages);
			$perf->step(sprintf('persist %d messages in database', count($chunk)));
			// Free the memory
			unset($messages);
		}

		if (!$imapMessages['all']) {
			// We might need more attempts to fill the cache
			$loggingMailboxId = $account->getId() . ':' . $mailbox->getName();
			$total = $imapMessages['total'];
			$cached = count($this->messageMapper->findAllUids($mailbox));
			$perf->step('find number of cached UIDs');

			$perf->end();
			throw new IncompleteSyncException("Initial sync is not complete for $loggingMailboxId ($cached of $total messages cached).");
		}

		$mailbox->setSyncNewToken($client->getSyncToken($mailbox->getName()));
		$mailbox->setSyncChangedToken($client->getSyncToken($mailbox->getName()));
		$mailbox->setSyncVanishedToken($client->getSyncToken($mailbox->getName()));
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
			'partial sync ' . $account->getId() . ':' . $mailbox->getName(),
			$logger
		);

		$uids = $knownUids ?? $this->dbMapper->findAllUids($mailbox);
		$perf->step('get all known UIDs');

		$requestId = base64_encode(random_bytes(16));

		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			$response = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncNewToken(),
					$uids
				),
				$account->getUserId(),
				$hasQresync,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS
			);
			$perf->step('get new messages via Horde');

			$highestKnownUid = $this->dbMapper->findHighestUid($mailbox);
			if ($highestKnownUid === null) {
				// Everything is relevant
				$newMessages = $response->getNewMessages();
			} else {
				// Filter out anything that is already in the DB. Ideally this never happens, but if there is an error
				// during a consecutive chunk INSERT, the sync token won't be updated. In that case the same message(s)
				// will be seen as *new* and therefore cause conflicts.
				$newMessages = array_filter($response->getNewMessages(), static function (IMAPMessage $imapMessage) use ($highestKnownUid) {
					return $imapMessage->getUid() > $highestKnownUid;
				});
			}

			foreach (array_chunk($newMessages, 500) as $chunk) {
				$dbMessages = array_map(static function (IMAPMessage $imapMessage) use ($mailbox, $account) {
					return $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount());
				}, $chunk);

				$this->dbMapper->insertBulk($account, ...$dbMessages);

				$this->dispatcher->dispatch(
					NewMessagesSynchronized::class,
					new NewMessagesSynchronized($account, $mailbox, $dbMessages)
				);
				$perf->step('classified a chunk of new messages');
			}
			$perf->step('persist new messages');

			$mailbox->setSyncNewToken($client->getSyncToken($mailbox->getName()));
			$newOrVanished = $newMessages !== [];
		}
		if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
			$response = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncChangedToken(),
					$uids
				),
				$account->getUserId(),
				$hasQresync,
				Horde_Imap_Client::SYNC_FLAGSUIDS
			);
			$perf->step('get changed messages via Horde');

			$permflagsEnabled = $this->mailManager->isPermflagsEnabled($client, $account, $mailbox->getName());

			foreach (array_chunk($response->getChangedMessages(), 500) as $chunk) {
				$this->dbMapper->updateBulk($account, $permflagsEnabled, ...array_map(static function (IMAPMessage $imapMessage) use ($mailbox, $account) {
					return $imapMessage->toDbMessage($mailbox->getId(), $account->getMailAccount());
				}, $chunk));
			}
			$perf->step('persist changed messages');

			// If a list of UIDs was *provided* (as opposed to loaded from the DB,
			// we can not assume that all changes were detected, hence this is kinda
			// a silent sync and we don't update the change token until the next full
			// mailbox sync
			if ($knownUids === null) {
				$mailbox->setSyncChangedToken($client->getSyncToken($mailbox->getName()));
			}
		}
		if ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS) {
			$response = $this->synchronizer->sync(
				$client,
				new Request(
					$requestId,
					$mailbox->getName(),
					$mailbox->getSyncVanishedToken(),
					$uids
				),
				$account->getUserId(),
				$hasQresync,
				Horde_Imap_Client::SYNC_VANISHEDUIDS
			);
			$perf->step('get vanished messages via Horde');

			foreach (array_chunk($response->getVanishedMessageUids(), 500) as $chunk) {
				$this->dbMapper->deleteByUid($mailbox, ...$chunk);
			}
			$perf->step('delete vanished messages');

			// If a list of UIDs was *provided* (as opposed to loaded from the DB,
			// we can not assume that all changes were detected, hence this is kinda
			// a silent sync and we don't update the vanish token until the next full
			// mailbox sync
			if ($knownUids === null) {
				$mailbox->setSyncVanishedToken($client->getSyncToken($mailbox->getName()));
			}
			$newOrVanished = $newOrVanished || !empty($response->getVanishedMessageUids());
		}
		$this->mailboxMapper->update($mailbox);
		$perf->end();

		return $newOrVanished;
	}
}
