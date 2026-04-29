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
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DbMessageMapper;
use OCA\Mail\Db\MessageTagsMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\ProtocolFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_values;

class MailManager {

	public function __construct(
		private readonly MailboxMapper $mailboxMapper,
		private readonly FolderMapper $folderMapper,
		private readonly ImapMessageMapper $imapMessageMapper,
		private readonly DbMessageMapper $dbMessageMapper,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly LoggerInterface $logger,
		private readonly TagMapper $tagMapper,
		private readonly MessageTagsMapper $messageTagsMapper,
		private readonly ProtocolFactory $protocolFactory,
		private readonly ImapFlag $imapFlag,
	) {
	}

	//** ============================ Mailbox Operations ============================ */

	public function getMailbox(string $uid, int $id): Mailbox {
		try {
			return $this->mailboxMapper->findByUid($id, $uid);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $id does not exist", 0, $e);
		}
	}

	/**
	 * @param Account $account
	 * @param bool $forceSync
	 *
	 * @return Mailbox[]
	 * @throws ServiceException
	 */
	public function getMailboxes(Account $account, bool $forceSync = false): array {
		$this->protocolFactory
			->mailboxConnector($account)
			->syncAccount($account, $forceSync);

		return $this->mailboxMapper->findAll($account);
	}

	public function createMailbox(Account $account, string $name, array $specialUse = []): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->createMailbox($account, $name, $specialUse);
	}

	public function renameMailbox(Account $account, Mailbox $mailbox, string $name): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->renameMailbox($account, $mailbox, $name);
	}

	public function deleteMailbox(Account $account, Mailbox $mailbox): void {
		$this->protocolFactory
			->mailboxConnector($account)
			->deleteMailbox($account, $mailbox);
	}

	public function updateSubscription(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->subscribeMailbox($account, $mailbox, $subscribed);
	}

	//** ============================ Message Operations ============================ */

	public function getMessage(string $uid, int $id): Message {
		return $this->dbMessageMapper->findByUserId($uid, $id);
	}

	public function getImapMessage(Account $account, Mailbox $mailbox, int $uid, bool $loadBody = false): IMAPMessage {
		return $this->protocolFactory
			->messageConnector($account)
			->fetchMessage($account, $mailbox, $uid, $loadBody);
	}

	/**
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string
	 *
	 * @throws ServiceException
	 */
	public function getRawMessage(Account $account, string $mailbox, int $uid): ?string {
		try {
			$mailboxEntity = $this->mailboxMapper->find($account, $mailbox);
			return $this->protocolFactory
				->messageConnector($account)
				->fetchMessageRaw($account, $mailboxEntity, $uid);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('Could not load message', 0, $e);
		}
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $uids
	 * @return IMAPMessage[]
	 * @throws ServiceException
	 */
	public function getImapMessagesForScheduleProcessing(Account $account, Mailbox $mailbox, array $uids): array {
		$client = $this->protocolFactory->imapClient($account);
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

	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int {
		return $this->dbMessageMapper->getIdForUid($mailbox, $uid);
	}

	/**
	 * @return Message[]
	 */
	public function getMessagesByMessageId(Account $account, string $messageId): array {
		return $this->dbMessageMapper->findByMessageId($account, $messageId);
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
	public function moveMessage(Account $sourceAccount,
		string $sourceFolderId,
		int $uid,
		Account $destinationAccount,
		string $destFolderId,
	): ?int {
		if ($sourceAccount->getId() === $destinationAccount->getId()) {
			try {
				$sourceMailbox = $this->mailboxMapper->find($sourceAccount, $sourceFolderId);
			} catch (DoesNotExistException $e) {
				throw new ServiceException("Source mailbox $sourceFolderId does not exist", 0, $e);
			}

			$newUid = $this->protocolFactory
				->messageConnector($sourceAccount)
				->moveMessage($sourceAccount, $sourceFolderId, $uid, $destFolderId);

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
	public function deleteMessage(Account $account, string|Mailbox $mailbox, int $messageUid): void {
		try {
			$sourceMailbox = is_string($mailbox)
				? $this->mailboxMapper->find($account, $mailbox)
				: $mailbox;
		} catch (DoesNotExistException $e) {
			$name = is_string($mailbox) ? $mailbox : (string)$mailbox->getId();
			throw new ServiceException("Source mailbox $name does not exist", 0, $e);
		}

		$this->protocolFactory
			->messageConnector($account)
			->deleteMessage($account, $sourceMailbox, $messageUid);
	}

	public function flagMessages(Account $account, string $flag, bool $value, Message ...$messages): void {
		$messages = $this->protocolFactory
			->messageConnector($account)
			->flagMessages($account, $flag, $value, ...$messages);
	}

	public function tagMessages(Account $account, Tag $tag, bool $value, Message ...$messages): void {
		$this->protocolFactory
			->messageConnector($account)
			->tagMessages($account, $tag, $value, ...$messages);
	}

	public function markFolderAsRead(Account $account, Mailbox $mailbox): void {
		// find all messages in mailbox with their remote ids
		$messages = $this->dbMessageMapper->findByUids($mailbox, $this->dbMessageMapper->findAllUids($mailbox));
		if ($messages === []) {
			return;
		}
		$this->flagMessages($account, 'seen', true, ...$messages);
	}

	public function enableMailboxBackgroundSync(Mailbox $mailbox,
		bool $syncInBackground): Mailbox {
		$mailbox->setSyncInBackground($syncInBackground);

		return $this->mailboxMapper->update($mailbox);
	}

	public function getThread(Account $account, string $threadRootId): array {
		return $this->dbMessageMapper->findThread($account, $threadRootId);
	}

	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array {
		return $this->protocolFactory
			->messageConnector($srcAccount)
			->moveThread($srcAccount, $srcMailbox, $dstAccount, $dstMailbox, $threadRootId);
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void {
		$this->protocolFactory
			->messageConnector($account)
			->deleteThread($account, $mailbox, $threadRootId);
	}


	/**
	 * @param Account $account
	 *
	 * @return Quota|null
	 * @see https://tools.ietf.org/html/rfc2087
	 */
	public function getQuota(Account $account): ?Quota {
		return $this->protocolFactory
			->messageConnector($account)
			->getQuota($account);
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
		$this->protocolFactory
			->messageConnector($account)
			->clearMailbox($account, $mailbox);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message $message
	 * @return Attachment[]
	 */
	public function getMailAttachments(Account $account, Mailbox $mailbox, Message $message): array {
		return $this->protocolFactory
			->messageConnector($account)
			->fetchAttachments($account, $mailbox, $message->getUid());
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
	public function getMailAttachment(Account $account,
		Mailbox $mailbox,
		Message $message,
		string $attachmentId): Attachment {
		return $this->protocolFactory
			->messageConnector($account)
			->fetchAttachment($account, $mailbox, $message->getUid(), $attachmentId);
	}

	/**
	 * Check IMAP server for support for PERMANENTFLAGS
	 *
	 * @param Account $account
	 * @param string $mailbox
	 * @return boolean
	 */
	public function isPermflagsEnabled(Account $account, string $mailbox): bool {
		return $this->protocolFactory
			->messageConnector($account)
			->isPermflagsEnabled($account, $mailbox);
	}

	/**
	 * @param string $imapLabel
	 * @param string $userId
	 * @return Tag
	 * @throws ClientException
	 */
	#[\Override]
	public function getTagByLabel(string $imapLabel, string $userId): Tag {
		try {
			return $this->tagMapper->getTagByImapLabel($imapLabel, $userId);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Unknown Tag', 0, $e);
		}
	}

	public function createTag(string $displayName, string $color, string $userId): Tag {
		try {
			$imapLabel = $this->imapFlag->create($displayName);
		} catch (ImapFlagEncodingException $e) {
			throw new ClientException('Error converting display name to UTF7-IMAP ', 0, $e);
		}

		try {
			try {
				return $this->tagMapper->getTagByImapLabel($imapLabel, $userId);
			} catch (DoesNotExistException $e) {
				throw new ClientException('Unknown Tag', 0, $e);
			}
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

	/** 
	 * @param int $id tag id
	 * @param string $userId user id of the tag owner
	 * @param Account[] $accounts accounts to remove the tag from
	 */
	public function deleteTag(int $id, string $userId, array $accounts): Tag {
		try {
			$tag = $this->tagMapper->getTagForUser($id, $userId);
		} catch (DoesNotExistException $e) {
			throw new ClientException('Tag not found', 0, $e);
		}

		foreach ($accounts as $account) {
			// find all messages with this tag
			try {
				$messageTags = $this->messageTagsMapper->getMessagesByTag($id);
				$messages = array_merge(... array_map(fn ($messageTag) => $this->getMessagesByMessageId($account, $messageTag->getImapMessageId()), array_values($messageTags)));
			} catch (DoesNotExistException $e) {
				throw new ClientException('Messages not found', 0, $e);
			}
			if ($messages === []) {
				continue;
			}
			
			$this->protocolFactory
				->messageConnector($account)
				->tagMessages($client, $account, $mailbox, $tag, false, $messages);
		}
		
		// update the local store
		foreach ($messageTags as $messageTag) {
			$this->messageTagsMapper->delete($messageTag);
		}

		return $this->tagMapper->delete($tag);
	}

	// ============================ Helpers ============================ */
	private function mapMailboxesById(array $mailboxes): array {
		$mailboxesById = [];
		foreach ($mailboxes as $mailbox) {
			$mailboxesById[$mailbox->getId()] = $mailbox;
		}
		return $mailboxesById;
	}

}
