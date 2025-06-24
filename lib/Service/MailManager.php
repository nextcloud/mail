<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use Horde_Imap_Client_Socket;
use Horde_Mime_Exception;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\MessageTagsMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeDecryptException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\ImapFlag;
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
	 * https://datatracker.ietf.org/doc/html/rfc9051#name-flags-message-attribute
	 */
	private const SYSTEM_FLAGS = [
		'seen' => [Horde_Imap_Client::FLAG_SEEN],
		'answered' => [Horde_Imap_Client::FLAG_ANSWERED],
		'flagged' => [Horde_Imap_Client::FLAG_FLAGGED],
		'deleted' => [Horde_Imap_Client::FLAG_DELETED],
		'draft' => [Horde_Imap_Client::FLAG_DRAFT],
		'recent' => [Horde_Imap_Client::FLAG_RECENT],
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

	/** @var MessageTagsMapper */
	private $messageTagsMapper;

	/** @var ThreadMapper */
	private $threadMapper;

	public function __construct(
		IMAPClientFactory $imapClientFactory,
		MailboxMapper $mailboxMapper,
		MailboxSync $mailboxSync,
		FolderMapper $folderMapper,
		ImapMessageMapper $messageMapper,
		DbMessageMapper $dbMessageMapper,
		IEventDispatcher $eventDispatcher,
		LoggerInterface $logger,
		TagMapper $tagMapper,
		MessageTagsMapper $messageTagsMapper,
		ThreadMapper $threadMapper,
		private ImapFlag $imapFlag,
	) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->imapMessageMapper = $messageMapper;
		$this->dbMessageMapper = $dbMessageMapper;
		$this->eventDispatcher = $eventDispatcher;
		$this->logger = $logger;
		$this->tagMapper = $tagMapper;
		$this->messageTagsMapper = $messageTagsMapper;
		$this->threadMapper = $threadMapper;
	}

	#[\Override]
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
	#[\Override]
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
	#[\Override]
	public function createMailbox(Account $account, string $name): Mailbox {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$folder = $this->folderMapper->createFolder($client, $name);
			$this->folderMapper->fetchFolderAcls([$folder], $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get mailbox status: ' . $e->getMessage(),
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
	 * @throws SmimeDecryptException
	 */
	#[\Override]
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
				$account->getUserId(),
				$loadBody
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException(
				'Could not load message',
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
				$uids,
				$account->getUserId(),
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

	#[\Override]
	public function getThread(Account $account, string $threadRootId): array {
		return $this->dbMessageMapper->findThread($account, $threadRootId);
	}

	#[\Override]
	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int {
		return $this->dbMessageMapper->getIdForUid($mailbox, $uid);
	}

	#[\Override]
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
	#[\Override]
	public function getSource(Horde_Imap_Client_Socket $client,
		Account $account,
		string $mailbox,
		int $uid): ?string {
		try {
			return $this->imapMessageMapper->getFullText(
				$client,
				$mailbox,
				$uid,
				$account->getUserId(),
				false,
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException('Could not load message', 0, $e);
		}
	}

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $uid
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 *
	 * @return ?int the new UID (or null if couldn't be determined)
	 * @throws ServiceException
	 */
	#[\Override]
	public function moveMessage(Account $sourceAccount,
		string $sourceFolderId,
		int $uid,
		Account $destinationAccount,
		string $destFolderId): ?int {
		if ($sourceAccount->getId() === $destinationAccount->getId()) {
			try {
				$sourceMailbox = $this->mailboxMapper->find($sourceAccount, $sourceFolderId);
			} catch (DoesNotExistException $e) {
				throw new ServiceException("Source mailbox $sourceFolderId does not exist", 0, $e);
			}

			$newUid = $this->moveMessageOnSameAccount(
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

			return $newUid;
		} else {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 * @todo evaluate if we should sync mailboxes first
	 */
	#[\Override]
	public function deleteMessage(Account $account,
		string $mailboxId,
		int $messageUid): void {
		try {
			$sourceMailbox = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("Source mailbox $mailboxId does not exist", 0, $e);
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->deleteMessageWithClient($account, $sourceMailbox, $messageUid, $client);
		} finally {
			$client->logout();
		}
	}

	/**
	 * @throws ServiceException
	 * @throws ClientException
	 * @throws TrashMailboxNotSetException
	 *
	 * @todo evaluate if we should sync mailboxes first
	 */
	#[\Override]
	public function deleteMessageWithClient(
		Account $account,
		Mailbox $mailbox,
		int $messageUid,
		Horde_Imap_Client_Socket $client,
	): void {
		$this->eventDispatcher->dispatchTyped(
			new BeforeMessageDeletedEvent($account, $mailbox->getName(), $messageUid)
		);

		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('No trash folder', 0, $e);
		}

		if ($mailbox->getName() === $trashMailbox->getName()) {
			// Delete inside trash -> expunge
			$this->imapMessageMapper->expunge(
				$client,
				$mailbox->getName(),
				$messageUid
			);
		} else {
			$this->imapMessageMapper->move(
				$client,
				$mailbox->getName(),
				$messageUid,
				$trashMailbox->getName()
			);
		}

		$this->eventDispatcher->dispatchTyped(
			new MessageDeletedEvent($account, $mailbox, $messageUid)
		);
	}

	/**
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
	 *
	 * @return ?int the new UID (or null if it couldn't be determined)
	 * @throws ServiceException
	 *
	 */
	private function moveMessageOnSameAccount(Account $account,
		string $sourceFolderId,
		string $destFolderId,
		int $messageId): ?int {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->imapMessageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function markFolderAsRead(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->imapMessageMapper->markAllRead($client, $mailbox->getName());
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function updateSubscription(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		/**
		 * 1. Change subscription on IMAP
		 */
		$client = $this->imapClientFactory->getClient($account);
		try {
			$client->subscribeMailbox($mailbox->getName(), $subscribed);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not set subscription status for mailbox ' . $mailbox->getId() . ' on IMAP: ' . $e->getMessage(),
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

	#[\Override]
	public function enableMailboxBackgroundSync(Mailbox $mailbox,
		bool $syncInBackground): Mailbox {
		$mailbox->setSyncInBackground($syncInBackground);

		return $this->mailboxMapper->update($mailbox);
	}

	#[\Override]
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
				'Could not set message flag on IMAP: ' . $e->getMessage(),
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
	 * Tag (flag) multiple messages on IMAP using a given client instance
	 *
	 * @param Message[] $messages
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function tagMessagesWithClient(Horde_Imap_Client_Socket $client, Account $account, Mailbox $mailbox, array $messages, Tag $tag, bool $value):void {
		if ($this->isPermflagsEnabled($client, $account, $mailbox->getName()) === true) {
			$messageIds = array_map(static function (Message $message) {
				return $message->getUid();
			}, $messages);
			try {
				if ($value) {
					// imap keywords and flags work the same way
					$this->imapMessageMapper->addFlag($client, $mailbox, $messageIds, $tag->getImapLabel());
				} else {
					$this->imapMessageMapper->removeFlag($client, $mailbox, $messageIds, $tag->getImapLabel());
				}
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException(
					'Could not set message keyword on IMAP: ' . $e->getMessage(),
					$e->getCode(),
					$e
				);
			}
		}

		if ($value) {
			foreach ($messages as $message) {
				$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
			}
		} else {
			foreach ($messages as $message) {
				$this->tagMapper->untagMessage($tag, $message->getMessageId());
			}
		}
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
	#[\Override]
	public function tagMessage(Account $account, string $mailbox, Message $message, Tag $tag, bool $value): void {
		try {
			$mb = $this->mailboxMapper->find($account, $mailbox);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $mailbox does not exist", 0, $e);
		}
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->tagMessagesWithClient($client, $account, $mb, [$message], $tag, $value);
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param Account $account
	 *
	 * @return Quota|null
	 * @see https://tools.ietf.org/html/rfc2087
	 */
	#[\Override]
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
		$storageQuotas = array_map(static function (array $root) {
			return $root['storage'] ?? [
				'usage' => 0,
				'limit' => 0,
			];
		}, array_merge(...array_values($quotas)));

		if ($storageQuotas === []) {
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

	#[\Override]
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
	#[\Override]
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
	#[\Override]
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
	 * @return Attachment[]
	 */
	#[\Override]
	public function getMailAttachments(Account $account, Mailbox $mailbox, Message $message): array {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->imapMessageMapper->getAttachments(
				$client,
				$mailbox->getName(),
				$message->getUid(),
				$account->getUserId(),
			);
		} finally {
			$client->logout();
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message $message
	 * @param string $attachmentId
	 * @return Attachment
	 *
	 * @throws DoesNotExistException
	 * @throws Horde_Imap_Client_Exception
	 * @throws Horde_Imap_Client_Exception_NoSupportExtension
	 * @throws ServiceException
	 * @throws Horde_Mime_Exception
	 */
	#[\Override]
	public function getMailAttachment(Account $account,
		Mailbox $mailbox,
		Message $message,
		string $attachmentId): Attachment {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->imapMessageMapper->getAttachment(
				$client,
				$mailbox->getName(),
				$message->getUid(),
				$attachmentId,
				$account->getUserId(),
			);
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
	#[\Override]
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
		// check if flag is RFC defined system flag
		if (array_key_exists($flag, self::SYSTEM_FLAGS) === true) {
			return self::SYSTEM_FLAGS[$flag];
		}
		// check if server supports custom keywords / this specific keyword
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}
		// check if server returned supported flags
		if (!isset($capabilities['permflags'])) {
			return [];
		}
		// check if server supports custom flags or specific flag
		if (in_array("\*", $capabilities['permflags']) || in_array($flag, $capabilities['permflags'])) {
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
	#[\Override]
	public function isPermflagsEnabled(Horde_Imap_Client_Socket $client, Account $account, string $mailbox): bool {
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}
		return (is_array($capabilities) === true && array_key_exists('permflags', $capabilities) === true && in_array("\*", $capabilities['permflags'], true) === true);
	}

	#[\Override]
	public function createTag(string $displayName, string $color, string $userId): Tag {
		try {
			$imapLabel = $this->imapFlag->create($displayName);
		} catch (ImapFlagEncodingException $e) {
			throw new ClientException('Error converting display name to UTF7-IMAP ', 0, $e);
		}

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

	#[\Override]
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

	#[\Override]
	public function deleteTag(int $id, string $userId, array $accounts) :Tag {
		try {
			$tag = $this->tagMapper->getTagForUser($id, $userId);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Tag not found', 0, $e);
		}

		foreach ($accounts as $account) {
			$this->deleteTagForAccount($id, $userId, $tag, $account);
		}
		return $this->tagMapper->delete($tag);
	}

	#[\Override]
	public function deleteTagForAccount(int $id, string $userId, Tag $tag, Account $account) :void {
		try {
			$messageTags = $this->messageTagsMapper->getMessagesByTag($id);
			$messages = array_merge(... array_map(function ($messageTag) use ($account) {
				return $this->getByMessageId($account, $messageTag->getImapMessageId());
			}, array_values($messageTags)));
		} catch (DoesNotExistException $e) {
			throw new ClientException('Messages not found', 0, $e);
		}

		$client = $this->imapClientFactory->getClient($account);

		foreach ($messageTags as $messageTag) {
			$this->messageTagsMapper->delete($messageTag);
		}
		$groupedMessages = [];
		foreach ($messages as $message) {
			$mailboxId = $message->getMailboxId();
			if (array_key_exists($mailboxId, $groupedMessages)) {
				$groupedMessages[$mailboxId][] = $message;
			} else {
				$groupedMessages[$mailboxId] = [$message];
			}
		}
		try {
			foreach ($groupedMessages as $mailboxId => $messages) {
				$mailbox = $this->getMailbox($userId, $mailboxId);
				$this->tagMessagesWithClient($client, $account, $mailbox, $messages, $tag, false);
			}
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array {
		$mailAccount = $srcAccount->getMailAccount();
		$messageInTrash = $srcMailbox->getId() === $mailAccount->getTrashMailboxId();

		$messages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash
		);

		$newUids = [];
		foreach ($messages as $message) {
			$this->logger->debug('move message', [
				'messageId' => $message['messageUid'],
				'srcMailboxId' => $srcMailbox->getId(),
				'dstMailboxId' => $dstMailbox->getId()
			]);

			$newUid = $this->moveMessage(
				$srcAccount,
				$message['mailboxName'],
				$message['messageUid'],
				$dstAccount,
				$dstMailbox->getName()
			);
			if ($newUid !== null) {
				$newUids[] = $newUid;
			}
		}
		return $newUids;
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[\Override]
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
	#[\Override]
	public function getByMessageId(Account $account, string $messageId): array {
		return $this->dbMessageMapper->findByMessageId($account, $messageId);
	}
}
