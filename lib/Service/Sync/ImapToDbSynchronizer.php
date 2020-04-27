<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Service\Sync;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
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
use OCP\ILogger;
use Throwable;
use function array_chunk;
use function array_map;

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

	/** @var ILogger */
	private $logger;

	public function __construct(DatabaseMessageMapper $dbMapper,
								IMAPClientFactory $clientFactory,
								ImapMessageMapper $imapMapper,
								MailboxMapper $mailboxMapper,
								DatabaseMessageMapper $messageMapper,
								Synchronizer $synchronizer,
								IEventDispatcher $dispatcher,
								PerformanceLogger $performanceLogger,
								ILogger $logger) {
		$this->dbMapper = $dbMapper;
		$this->clientFactory = $clientFactory;
		$this->imapMapper = $imapMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->synchronizer = $synchronizer;
		$this->dispatcher = $dispatcher;
		$this->performanceLogger = $performanceLogger;
		$this->logger = $logger;
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function syncAccount(Account $account,
								bool $force = false,
								int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS): void {
		foreach ($this->mailboxMapper->findAll($account) as $mailbox) {
			$this->sync(
				$account,
				$mailbox,
				$criteria,
				null,
				$force
			);
		}
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
		$id = $account->getId() . ":" . $mailbox->getName();
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
		$id = $account->getId() . ":" . $mailbox->getName();
		$this->messageMapper->deleteAll($mailbox);
		$this->logger->debug("All messages of $id cleared");
		$mailbox->setSyncNewToken(null);
		$mailbox->setSyncChangedToken(null);
		$mailbox->setSyncVanishedToken(null);
		$this->mailboxMapper->update($mailbox);
	}

	/**
	 * @param int[] $knownUids
	 *
	 * @throws ClientException
	 * @throws MailboxNotCachedException
	 * @throws ServiceException
	 */
	public function sync(Account $account,
						 Mailbox $mailbox,
						 int $criteria = Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
						 array $knownUids = null,
						 bool $force = false): void {
		if ($mailbox->getSelectable() === false) {
			return;
		}

		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			$this->mailboxMapper->lockForNewSync($mailbox);
		}
		if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
			$this->mailboxMapper->lockForChangeSync($mailbox);
		}
		if ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS) {
			$this->mailboxMapper->lockForVanishedSync($mailbox);
		}

		try {
			if ($force
				|| $mailbox->getSyncNewToken() === null
				|| $mailbox->getSyncChangedToken() === null
				|| $mailbox->getSyncVanishedToken() === null) {
				$this->runInitialSync($account, $mailbox);
			} else {
				try {
					$this->runPartialSync($account, $mailbox, $criteria, $knownUids);
				} catch (UidValidityChangedException $e) {
					$this->logger->warning('Mailbox UID validity changed. Wiping cache and performing full sync.');
					$this->resetCache($account, $mailbox);
					$this->runInitialSync($account, $mailbox);
				}
			}
		} catch (ServiceException $e) {
			// Just rethrow, don't wrap into another exception
			throw $e;
		} catch (Throwable $e) {
			throw new ServiceException('Sync failed for ' . $account->getId() . ':' . $mailbox->getName() . ': ' . $e->getMessage(), 0, $e);
		} finally {
			if ($criteria & Horde_Imap_Client::SYNC_VANISHEDUIDS) {
				$this->mailboxMapper->unlockFromVanishedSync($mailbox);
			}
			if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
				$this->mailboxMapper->unlockFromChangedSync($mailbox);
			}
			if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
				$this->mailboxMapper->unlockFromNewSync($mailbox);
			}
		}
	}

	/**
	 * @throws ServiceException
	 * @throws IncompleteSyncException
	 */
	private function runInitialSync(Account $account, Mailbox $mailbox): void {
		$perf = $this->performanceLogger->start('Initial sync ' . $account->getId() . ':' . $mailbox->getName());

		$highestKnownUid = $this->dbMapper->findHighestUid($mailbox);
		$client = $this->clientFactory->getClient($account);
		try {
			$imapMessages = $this->imapMapper->findAll(
				$client,
				$mailbox->getName(),
				self::MAX_NEW_MESSAGES,
				$highestKnownUid ?? 0
			);
			$perf->step('fetch all messages from IMAP');
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Can not get messages from mailbox ' . $mailbox->getName() . ': ' . $e->getMessage(), 0, $e);
		}

		foreach (array_chunk($imapMessages['messages'], 500) as $chunk) {
			$this->dbMapper->insertBulk(...array_map(function (IMAPMessage $imapMessage) use ($mailbox) {
				return $imapMessage->toDbMessage($mailbox->getId());
			}, $chunk));
		}
		$perf->step('persist messages in database');

		if (!$imapMessages['all']) {
			// We might need more attempts to fill the cache
			$perf->end();

			$loggingMailboxId = $account->getId() . ':' . $mailbox->getName();
			$total = $imapMessages['total'];
			$cached = count($this->messageMapper->findAllUids($mailbox));
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
	 */
	private function runPartialSync(Account $account,
									Mailbox $mailbox,
									int $criteria,
									array $knownUids = null): void {
		$perf = $this->performanceLogger->start('partial sync ' . $account->getId() . ':' . $mailbox->getName());

		$client = $this->clientFactory->getClient($account);
		$uids = $knownUids ?? $this->dbMapper->findAllUids($mailbox);
		$perf->step('get all known UIDs');

		if ($criteria & Horde_Imap_Client::SYNC_NEWMSGSUIDS) {
			$response = $this->synchronizer->sync(
				$client,
				new Request(
					$mailbox->getName(),
					$mailbox->getSyncNewToken(),
					$uids
				),
				Horde_Imap_Client::SYNC_NEWMSGSUIDS
			);
			$perf->step('get new messages via Horde');

			foreach (array_chunk($response->getNewMessages(), 500) as $chunk) {
				$dbMessages = array_map(function (IMAPMessage $imapMessage) use ($mailbox) {
					return $imapMessage->toDbMessage($mailbox->getId());
				}, $chunk);

				$this->dispatcher->dispatch(
					NewMessagesSynchronized::class,
					new NewMessagesSynchronized($account, $mailbox, $dbMessages)
				);
				$perf->step('classified a chunk of new messages');

				$this->dbMapper->insertBulk(...$dbMessages);
			}
			$perf->step('persist new messages');

			$mailbox->setSyncNewToken($client->getSyncToken($mailbox->getName()));
		}
		if ($criteria & Horde_Imap_Client::SYNC_FLAGSUIDS) {
			$response = $this->synchronizer->sync(
				$client,
				new Request(
					$mailbox->getName(),
					$mailbox->getSyncChangedToken(),
					$uids
				),
				Horde_Imap_Client::SYNC_FLAGSUIDS
			);
			$perf->step('get changed messages via Horde');

			foreach (array_chunk($response->getChangedMessages(), 500) as $chunk) {
				$this->dbMapper->updateBulk(...array_map(function (IMAPMessage $imapMessage) use ($mailbox) {
					return $imapMessage->toDbMessage($mailbox->getId());
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
					$mailbox->getName(),
					$mailbox->getSyncVanishedToken(),
					$uids
				),
				Horde_Imap_Client::SYNC_VANISHEDUIDS
			);
			$perf->step('get vanished messages via Horde');

			foreach (array_chunk($response->getVanishedMessageUids(), 500) as $chunk) {
				$this->dbMapper->deleteByUid($mailbox, ...$chunk);
			}
			$perf->step('persist new messages');

			$mailbox->setSyncVanishedToken($client->getSyncToken($mailbox->getName()));
		}
		$this->mailboxMapper->update($mailbox);
		$perf->end();
	}
}
