<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
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
		'mdnsent' => [Horde_Imap_Client::FLAG_MDNSENT],
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

	/** @var LoggerInterface */
	private $logger;

	/** @var TagMapper */
	private $tagMapper;

	/** @var ThreadMapper */
	private $threadMapper;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MailboxSync $mailboxSync,
								FolderMapper $folderMapper,
								ImapMessageMapper $messageMapper,
								DbMessageMapper $dbMessageMapper,
								IEventDispatcher $eventDispatcher,
								LoggerInterface $logger,
								TagMapper $tagMapper,
								ThreadMapper $threadMapper) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->imapMessageMapper = $messageMapper;
		$this->dbMessageMapper = $dbMessageMapper;
		$this->eventDispatcher = $eventDispatcher;
		$this->logger = $logger;
		$this->tagMapper = $tagMapper;
		$this->threadMapper = $threadMapper;
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
		try {
			$folder = $this->folderMapper->createFolder($client, $account, $name);
			$this->folderMapper->getFoldersStatus([$folder], $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not get mailbox status: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		} finally {
			$client->logout();
		}
		$this->folderMapper->detectFolderSpecialUse([$folder]);

		$this->mailboxSync->sync($account, $this->logger, true);

		return $this->mailboxMapper->find($account, $name);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @param bool $loadBody
	 *
	 * @return IMAPMessage
	 *
	 * @throws ServiceException
	 */
	public function getImapMessage(Horde_Imap_Client_Socket $client,
								   Account $account,
								   Mailbox $mailbox,
								   int $uid,
								   bool $loadBody = false): IMAPMessage {
		try {
			return $this->imapMessageMapper->find(
				$client,
				$mailbox->getName(),
				$uid,
				$loadBody
			);
		} catch (Horde_Imap_Client_Exception | DoesNotExistException $e) {
			throw new ServiceException(
				"Could not load message",
				$e->getCode(),
				$e
			);
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $uids
	 * @return IMAPMessage[]
	 * @throws ServiceException
	 */
	public function getImapMessagesForScheduleProcessing(Account $account,
		Mailbox $mailbox,
		array $uids): array {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->imapMessageMapper->findByIds(
				$client,
				$mailbox->getName(),
				new Horde_Imap_Client_Ids($uids),
				true
			);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not load messages: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		} finally {
			$client->logout();
		}
	}

	public function getThread(Account $account, string $threadRootId): array {
		return $this->dbMessageMapper->findThread($account, $threadRootId);
	}

	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int {
		return $this->dbMessageMapper->getIdForUid($mailbox, $uid);
	}

	public function getMessage(string $uid, int $id): Message {
		return $this->dbMessageMapper->findByUserId($uid, $id);
	}

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string
	 *
	 * @throws ServiceException
	 */
	public function getSource(Horde_Imap_Client_Socket $client,
							  Account $account,
							  string $mailbox,
							  int $uid): ?string {
		try {
			return $this->imapMessageMapper->getFullText(
				$client,
				$mailbox,
				$uid
			);
		} catch (Horde_Imap_Client_Exception | DoesNotExistException $e) {
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

		$client = $this->imapClientFactory->getClient($account);
		try {
			if ($mailboxId === $trashMailbox->getName()) {
				// Delete inside trash -> expunge
				$this->imapMessageMapper->expunge(
					$client,
					$sourceMailbox->getName(),
					$messageId
				);
			} else {
				$this->imapMessageMapper->move(
					$client,
					$sourceMailbox->getName(),
					$messageId,
					$trashMailbox->getName()
				);
			}
		} finally {
			$client->logout();
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
		try {
			$this->imapMessageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
		} finally {
			$client->logout();
		}
	}

	public function markFolderAsRead(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->imapMessageMapper->markAllRead($client, $mailbox->getName());
		} finally {
			$client->logout();
		}
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
				$e->getCode(),
				$e
			);
		} finally {
			$client->logout();
		}

		/**
		 * 2. Pull changes into the mailbox database cache
		 */
		$this->mailboxSync->sync($account, $this->logger, true);

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
		try {
			$mb = $this->mailboxMapper->find($account, $mailbox);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $mailbox does not exist", 0, $e);
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			// Only send system flags to the IMAP server as other flags might not be supported
			$imapFlags = $this->filterFlags($client, $account, $flag, $mailbox);
			foreach ($imapFlags as $imapFlag) {
				if (empty($imapFlag) === true) {
					continue;
				}
				if ($value) {
					$this->imapMessageMapper->addFlag($client, $mb, [$uid], $imapFlag);
				} else {
					$this->imapMessageMapper->removeFlag($client, $mb, [$uid], $imapFlag);
				}
			}
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not set message flag on IMAP: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		} finally {
			$client->logout();
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
	 * Tag (flag) a message on IMAP
	 *
	 * @param Account $account
	 * @param string $mailbox
	 * @param Message $message
	 * @param Tag $tag
	 * @param boolean $value
	 * @return void
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 * @uses
	 *
	 * @link https://github.com/nextcloud/mail/issues/25
	 */
	public function tagMessage(Account $account, string $mailbox, Message $message, Tag $tag, bool $value): void {
		try {
			$mb = $this->mailboxMapper->find($account, $mailbox);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $mailbox does not exist", 0, $e);
		}
		$client = $this->imapClientFactory->getClient($account);
		if ($this->isPermflagsEnabled($client, $account, $mailbox) === true) {
			try {
				if ($value) {
					// imap keywords and flags work the same way
					$this->imapMessageMapper->addFlag($client, $mb, [$message->getUid()], $tag->getImapLabel());
				} else {
					$this->imapMessageMapper->removeFlag($client, $mb, [$message->getUid()], $tag->getImapLabel());
				}
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException(
					"Could not set message keyword on IMAP: " . $e->getMessage(),
					$e->getCode(),
					$e
				);
			} finally {
				$client->logout();
			}
		} else {
			$client->logout();
		}

		if ($value) {
			$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
		} else {
			$this->tagMapper->untagMessage($tag, $message->getMessageId());
		}
	}

	/**
	 * @param Account $account
	 *
	 * @return Quota|null
	 * @see https://tools.ietf.org/html/rfc2087
	 */
	public function getQuota(Account $account): ?Quota {
		/**
		 * Get all the quotas roots of the user's mailboxes
		 */
		$client = $this->imapClientFactory->getClient($account);
		try {
			$quotas = array_map(static function (Folder $mb) use ($client) {
				return $client->getQuotaRoot($mb->getMailbox());
			}, $this->folderMapper->getFolders($account, $client));
		} catch (Horde_Imap_Client_Exception_NoSupportExtension $ex) {
			return null;
		} finally {
			$client->logout();
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
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->folderMapper->renameFolder(
				$client,
				$mailbox->getName(),
				$name
			);
		} finally {
			$client->logout();
		}

		/**
		 * 2. Get the IMAP changes into our database cache
		 */
		$this->mailboxSync->sync($account, $this->logger, true);

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
		try {
			$this->folderMapper->delete($client, $mailbox->getName());
		} finally {
			$client->logout();
		}
		$this->mailboxMapper->delete($mailbox);
	}

	/**
	 * Clear messages in folder
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws ServiceException
	 */
	public function clearMailbox(Account $account,
								  Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		$currentMailboxId = $mailbox->getId();
		try {
			if (($currentMailboxId !== $trashMailboxId) && !is_null($trashMailboxId)) {
				$trash = $this->mailboxMapper->findById($trashMailboxId);
				$client->copy($mailbox->getName(), $trash->getName(), [
					'move' => true
				]);
			} else {
				$client->expunge($mailbox->getName(), [
					'delete' => true
				]);
			}
			$this->dbMessageMapper->deleteAll($mailbox);
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message $message
	 * @return array[]
	 */
	public function getMailAttachments(Account $account, Mailbox $mailbox, Message $message): array {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->imapMessageMapper->getAttachments($client, $mailbox->getName(), $message->getUid());
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param string $imapLabel
	 * @param string $userId
	 * @return Tag
	 * @throws ClientException
	 */
	public function getTagByImapLabel(string $imapLabel, string $userId): Tag {
		try {
			return $this->tagMapper->getTagByImapLabel($imapLabel, $userId);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Unknown Tag', 0, $e);
		}
	}

	/**
	 * Filter out IMAP flags that aren't supported by the client server
	 *
	 * @param string $flag
	 * @param string $mailbox
	 * @return array
	 */
	public function filterFlags(Horde_Imap_Client_Socket $client, Account $account, string $flag, string $mailbox): array {
		// check for RFC server flags
		if (array_key_exists($flag, self::ALLOWED_FLAGS) === true) {
			return self::ALLOWED_FLAGS[$flag];
		}

		// Only allow flag setting if IMAP supports Permaflags
		// @TODO check if there are length & char limits on permflags
		if ($this->isPermflagsEnabled($client, $account, $mailbox) === true) {
			return [$flag];
		}
		return [];
	}

	/**
	 * Check IMAP server for support for PERMANENTFLAGS
	 *
	 * @param Account $account
	 * @param string $mailbox
	 * @return boolean
	 */
	public function isPermflagsEnabled(Horde_Imap_Client_Socket $client, Account $account, string $mailbox): bool {
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				"Could not get message flag options from IMAP: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}
		return (is_array($capabilities) === true && array_key_exists('permflags', $capabilities) === true && in_array("\*", $capabilities['permflags'], true) === true);
	}

	public function createTag(string $displayName, string $color, string $userId): Tag {
		$imapLabel = str_replace(' ', '_', $displayName);
		/** @var string|false $imapLabel */
		$imapLabel = mb_convert_encoding($imapLabel, 'UTF7-IMAP', 'UTF-8');
		if ($imapLabel === false) {
			throw new ClientException('Error converting display name to UTF7-IMAP ', 0);
		}
		$imapLabel = '$' . strtolower(mb_strcut($imapLabel, 0, 63));

		try {
			return $this->getTagByImapLabel($imapLabel, $userId);
		} catch (ClientException $e) {
			// it's valid that a tag does not exist.
		}

		$tag = new Tag();
		$tag->setUserId($userId);
		$tag->setDisplayName($displayName);
		$tag->setImapLabel($imapLabel);
		$tag->setColor($color);
		$tag->setIsDefaultTag(false);

		return $this->tagMapper->insert($tag);
	}

	public function updateTag(int $id, string $displayName, string $color, string $userId): Tag {
		try {
			$tag = $this->tagMapper->getTagForUser($id, $userId);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Tag not found', 0, $e);
		}

		$tag->setDisplayName($displayName);
		$tag->setColor($color);

		return $this->tagMapper->update($tag);
	}

	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): void {
		$mailAccount = $srcAccount->getMailAccount();
		$messageInTrash = $srcMailbox->getId() === $mailAccount->getTrashMailboxId();

		$messages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash
		);

		foreach ($messages as $message) {
			$this->logger->debug('move message', [
				'messageId' => $message['messageUid'],
				'srcMailboxId' => $srcMailbox->getId(),
				'dstMailboxId' => $dstMailbox->getId()
			]);

			$this->moveMessage(
				$srcAccount,
				$message['mailboxName'],
				$message['messageUid'],
				$dstAccount,
				$dstMailbox->getName()
			);
		}
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void {
		$mailAccount = $account->getMailAccount();
		$messageInTrash = $mailbox->getId() === $mailAccount->getTrashMailboxId();

		$messages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash
		);

		foreach ($messages as $message) {
			$this->logger->debug('deleting message', [
				'messageId' => $message['messageUid'],
				'mailboxId' => $mailbox->getId(),
			]);

			$this->deleteMessage(
				$account,
				$message['mailboxName'],
				$message['messageUid']
			);
		}
	}

	/**
	 * @return Message[]
	 */
	public function getByMessageId(Account $account, string $messageId): array {
		return $this->dbMessageMapper->findByMessageId($account, $messageId);
	}
}
