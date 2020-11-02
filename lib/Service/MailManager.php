<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_values;

class MailManager implements IMailManager {

	/**
	 * https://tools.ietf.org/html/rfc3501#section-2.3.2
	 */
	private const ALLOWED_FLAGS = [
		'seen' => [Horde_Imap_Client::FLAG_SEEN],
		'answered' => [Horde_Imap_Client::FLAG_ANSWERED],
		'flagged' => [Horde_Imap_Client::FLAG_FLAGGED],
		'deleted' => [Horde_Imap_Client::FLAG_DELETED],
		'draft' => [Horde_Imap_Client::FLAG_DRAFT],
		'recent' => [Horde_Imap_Client::FLAG_RECENT],
		'junk' => [Horde_Imap_Client::FLAG_JUNK, 'junk'],
	];

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var ImapMessageMapper */
	private $imapMessageMapper;

	/** @var DbMessageMapper */
	private $dbMessageMapper;

	/** @var IEventDispatcher */
	private $eventDispatcher;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MailboxSync $mailboxSync,
								FolderMapper $folderMapper,
								ImapMessageMapper $messageMapper,
								DbMessageMapper $dbMessageMapper,
								IEventDispatcher $eventDispatcher,
								LoggerInterface $logger) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->imapMessageMapper = $messageMapper;
		$this->dbMessageMapper = $dbMessageMapper;
		$this->eventDispatcher = $eventDispatcher;
		$this->logger = $logger;
	}

	public function getMailbox(string $uid, int $id): Mailbox {
		try {
			return $this->mailboxMapper->findByUid($id, $uid);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $id does not exist", 0, $e);
		}
	}

	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 * @throws ServiceException
	 */
	public function getMailboxes(Account $account): array {
		$this->mailboxSync->sync($account, $this->logger);

		return $this->mailboxMapper->findAll($account);
	}

	/**
	 * @param Account $account
	 * @param string $name
	 *
	 * @return Mailbox
	 * @throws ServiceException
	 */
	public function createMailbox(Account $account, string $name): Mailbox {
		$client = $this->imapClientFactory->getClient($account);

		$folder = $this->folderMapper->createFolder($client, $account, $name);
		try {
			$this->folderMapper->getFoldersStatus([$folder], $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not get mailbox status: " .
				$e->getMessage(),
				(int) $e->getCode(),
				$e
			);
		}
		$this->folderMapper->detectFolderSpecialUse([$folder]);

		$this->mailboxSync->sync($account, $this->logger,true);

		return $this->mailboxMapper->find($account, $name);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @return FolderStats
	 * @throws Horde_Imap_Client_Exception
	 */
	public function getMailboxStats(Account $account, Mailbox $mailbox): FolderStats {
		$client = $this->imapClientFactory->getClient($account);

		return $this->folderMapper->getFoldersStatusAsObject($client, $mailbox->getName());
	}

	public function getImapMessage(Account $account,
								   Mailbox $mailbox,
								   int $uid,
								   bool $loadBody = false): IMAPMessage {
		$client = $this->imapClientFactory->getClient($account);

		try {
			return $this->imapMessageMapper->find(
				$client,
				$mailbox->getName(),
				$uid,
				$loadBody
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException(
				"Could not load message",
				(int) $e->getCode(),
				$e
			);
		}
	}

	public function getThread(Account $account, int $messageId): array {
		return $this->dbMessageMapper->findThread($account, $messageId);
	}

	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int {
		return $this->dbMessageMapper->getIdForUid($mailbox, $uid);
	}

	public function getMessage(string $uid, int $id): Message {
		return $this->dbMessageMapper->findByUserId($uid, $id);
	}

	/**
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Account $account, string $mailbox, int $uid): ?string {
		$client = $this->imapClientFactory->getClient($account);

		try {
			return $this->imapMessageMapper->getSource(
				$client,
				$mailbox,
				$uid
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException("Could not load message", 0, $e);
		}
	}

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $uid
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 *
	 * @return void
	 * @throws ServiceException
	 *
	 */
	public function moveMessage(Account $sourceAccount,
								string $sourceFolderId,
								int $uid,
								Account $destinationAccount,
								string $destFolderId) {
		if ($sourceAccount->getId() === $destinationAccount->getId()) {
			try {
				$sourceMailbox = $this->mailboxMapper->find($sourceAccount, $sourceFolderId);
			} catch (DoesNotExistException $e) {
				throw new ServiceException("Source mailbox $sourceFolderId does not exist", 0, $e);
			}

			$this->moveMessageOnSameAccount(
				$sourceAccount,
				$sourceFolderId,
				$destFolderId,
				$uid
			);

			// Delete cached source message (the source imap message is copied and deleted)
			$this->eventDispatcher->dispatch(
				MessageDeletedEvent::class,
				new MessageDeletedEvent($sourceAccount, $sourceMailbox, $uid)
			);
		} else {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 * @todo evaluate if we should sync mailboxes first
	 */
	public function deleteMessage(Account $account,
								  string $mailboxId,
								  int $messageId): void {
		$this->eventDispatcher->dispatch(
			BeforeMessageDeletedEvent::class,
			new BeforeMessageDeletedEvent($account, $mailboxId, $messageId)
		);

		try {
			$sourceMailbox = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("Source mailbox $mailboxId does not exist", 0, $e);
		}
		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("No trash folder", 0, $e);
		}

		if ($mailboxId === $trashMailbox->getName()) {
			// Delete inside trash -> expunge
			$this->imapMessageMapper->expunge(
				$this->imapClientFactory->getClient($account),
				$sourceMailbox->getName(),
				$messageId
			);
		} else {
			$this->imapMessageMapper->move(
				$this->imapClientFactory->getClient($account),
				$sourceMailbox->getName(),
				$messageId,
				$trashMailbox->getName()
			);
		}

		$this->eventDispatcher->dispatch(
			MessageDeletedEvent::class,
			new MessageDeletedEvent($account, $sourceMailbox, $messageId)
		);
	}

	/**
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
	 *
	 * @return void
	 * @throws ServiceException
	 *
	 */
	private function moveMessageOnSameAccount(Account $account,
											  string $sourceFolderId,
											  string $destFolderId,
											  int $messageId): void {
		$client = $this->imapClientFactory->getClient($account);

		$this->imapMessageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
	}

	public function markFolderAsRead(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);

		$this->imapMessageMapper->markAllRead($client, $mailbox->getName());
	}

	public function updateSubscription(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		/**
		 * 1. Change subscription on IMAP
		 */
		$client = $this->imapClientFactory->getClient($account);
		try {
			$client->subscribeMailbox($mailbox->getName(), $subscribed);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not set subscription status for mailbox " . $mailbox->getId() . " on IMAP: " . $e->getMessage(),
				(int) $e->getCode(),
				$e
			);
		}

		/**
		 * 2. Pull changes into the mailbox database cache
		 */
		$this->mailboxSync->sync($account, $this->logger,true);

		/**
		 * 3. Return the updated object
		 */
		return $this->mailboxMapper->find($account, $mailbox->getName());
	}

	public function enableMailboxBackgroundSync(Mailbox $mailbox,
												bool $syncInBackground): Mailbox {
		$mailbox->setSyncInBackground($syncInBackground);

		return $this->mailboxMapper->update($mailbox);
	}

	public function flagMessage(Account $account, string $mailbox, int $uid, string $flag, bool $value): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$mb = $this->mailboxMapper->find($account, $mailbox);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $mailbox does not exist", 0, $e);
		}

		// Only send system flags to the IMAP server as other flags might not be supported
		$imapFlags = self::ALLOWED_FLAGS[$flag] ?? [];
		try {
			foreach ($imapFlags as $imapFlag) {
				if ($value) {
					$this->imapMessageMapper->addFlag($client, $mb, $uid, $imapFlag);
				} else {
					$this->imapMessageMapper->removeFlag($client, $mb, $uid, $imapFlag);
				}
			}
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not set message flag on IMAP: " . $e->getMessage(),
				(int) $e->getCode(),
				$e
			);
		}

		$this->eventDispatcher->dispatch(
			MessageFlaggedEvent::class,
			new MessageFlaggedEvent(
				$account,
				$mb,
				$uid,
				$flag,
				$value
			)
		);
	}

	/**
	 * @param Account $account
	 *
	 * @return Quota|null
	 * @see https://tools.ietf.org/html/rfc2087
	 */
	public function getQuota(Account $account): ?Quota {
		$client = $this->imapClientFactory->getClient($account);

		/**
		 * Get all the quotas roots of the user's mailboxes
		 */
		try {
			$quotas = array_map(static function (Folder $mb) use ($client) {
				return $client->getQuotaRoot($mb->getMailbox());
			}, $this->folderMapper->getFolders($account, $client));
		} catch (Horde_Imap_Client_Exception_NoSupportExtension $ex) {
			return null;
		}

		/**
		 * Extract the 'storage' quota
		 *
		 * Falls back to 0/0 if this quota has no storage information
		 *
		 * @see https://tools.ietf.org/html/rfc2087#section-3
		 */
		$storageQuotas = array_map(function (array $root) {
			return $root['storage'] ?? [
				'usage' => 0,
				'limit' => 0,
			];
		}, array_merge(...array_values($quotas)));

		if (empty($storageQuotas)) {
			// Nothing left to do, and array_merge doesn't like to be called with zero arguments.
			return null;
		}

		/**
		 * Deduplicate identical quota roots
		 */
		$storage = array_merge(...array_values($storageQuotas));

		return new Quota(
			1024 * (int)($storage['usage'] ?? 0),
			1024 * (int)($storage['limit'] ?? 0)
		);
	}

	public function renameMailbox(Account $account, Mailbox $mailbox, string $name): Mailbox {
		/*
		 * 1. Rename on IMAP
		 */
		$this->folderMapper->renameFolder(
			$this->imapClientFactory->getClient($account),
			$mailbox->getName(),
			$name
		);

		/**
		 * 2. Get the IMAP changes into our database cache
		 */
		$this->mailboxSync->sync($account, $this->logger,true);

		/**
		 * 3. Return the cached object with the new ID
		 */
		try {
			return $this->mailboxMapper->find($account, $name);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("The renamed mailbox $name does not exist", 0, $e);
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function deleteMailbox(Account $account,
								  Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		$this->folderMapper->delete($client, $mailbox->getName());
		$this->mailboxMapper->delete($mailbox);
	}
}
