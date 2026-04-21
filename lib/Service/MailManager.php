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
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ImapFlagEncodingException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\ImapFlag;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_values;

class MailManager {

	public function __construct(
		private readonly MailboxMapper $mailboxMapper,
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
			->syncAll($account, $forceSync);

		return $this->mailboxMapper->findAll($account);
	}

	public function createMailbox(Account $account, string $name, array $specialUse = []): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->create($account, $name, $specialUse);
	}

	public function renameMailbox(Account $account, Mailbox $mailbox, string $name): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->rename($account, $mailbox, $name);
	}

	public function deleteMailbox(Account $account, Mailbox $mailbox): void {
		$this->protocolFactory
			->mailboxConnector($account)
			->delete($account, $mailbox);
	}

	public function updateSubscription(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		return $this->protocolFactory
			->mailboxConnector($account)
			->subscribe($account, $mailbox, $subscribed);
	}

	public function clearMailbox(Account $account, Mailbox $mailbox): void {
		$this->protocolFactory
			->messageConnector($account)
			->clearMailbox($account, $mailbox);
	}

	//** ============================ Message Operations ============================ */

	public function getMessage(string $uid, int $id): Message {
		return $this->dbMessageMapper->findByUserId($uid, $id);
	}

	public function getImapMessage(Account $account, Mailbox $mailbox, Message $message, bool $loadBody = false): IMAPMessage {
		$messages = $this->protocolFactory
			->messageConnector($account)
			->fetchMessages($account, $mailbox, $loadBody, $message);
		
		if ($messages === []) {
			throw new ClientException('Message not found on remote server');
		}
		return reset($messages);
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int[] $uids
	 * @return IMAPMessage[]
	 * @throws ServiceException
	 */
	public function getImapMessages(Account $account, Mailbox $mailbox, bool $loadBody = false, Message ...$messages): array {
		return $this->protocolFactory
			->messageConnector($account)
			->fetchMessages($account, $mailbox, $loadBody, ...$messages);
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
	public function getRawMessage(Account $account, Mailbox $mailbox, Message $message): ?string {
		$message = $this->protocolFactory
			->messageConnector($account)
			->fetchMessageRaw($account, $mailbox, $message);
		if ($message === null) {
			throw new ClientException('Message not found on remote server');
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
	 * @return int[]
	 */
	public function findMessages(Account $account, Mailbox $mailbox, SearchQuery $searchQuery): array {
		return $this->protocolFactory
			->messageConnector($account)
			->findMessages($account, $mailbox, $searchQuery);
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
	public function moveMessage(Account $sourceAccount, Mailbox $sourceMailbox, Message $message, Account $destinationAccount, Mailbox $destinationMailbox): ?int {
		if ($sourceAccount->getId() !== $destinationAccount->getId()) {
			throw new ServiceException('It is not possible to move across accounts yet');
		}

		$mutatedUids = $this->moveMessages($sourceAccount, $destinationMailbox, $sourceMailbox, ...[$message]);

		return $mutatedUids[0] ?? null;
	}

	public function moveMessages(Account $account, Mailbox $targetMailbox, Mailbox $sourceMailbox, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		// update remote store
		$mutatedMessages = $this->protocolFactory
			->messageConnector($account)
			->moveMessages($account, $targetMailbox, $sourceMailbox, ...$messages);

		// update local store
		$this->dbMessageMapper->updateBulk($account, false, ...$mutatedMessages);

		$mutatedUids = [];
		foreach ($mutatedMessages as $mutatedMessage) {
			$mutatedUids[] = $mutatedMessage->getUid();
		}

		return $mutatedUids;
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 * @todo evaluate if we should sync mailboxes first
	 */
	public function deleteMessage(Account $account, Mailbox $mailbox, Message $message): void {
		$this->deleteMessages($account, $mailbox, ...[$message]);
	}

	public function deleteMessages(Account $account, Mailbox $sourceMailbox, Message ...$messages): void {
		if ($messages === []) {
			return;
		}
		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('No trash folder', 0, $e);
		}
		$operation = $sourceMailbox->getId() === $trashMailbox->getId() ? 'delete' : 'move';
		$mappedUids = [];

		// dispatch events and map objects to their original UIDs before mutation
		foreach ($messages as $message) {
			$this->eventDispatcher->dispatchTyped(new BeforeMessageDeletedEvent($account, $sourceMailbox->getName(), $message->getUid()));
			$this->logger->debug("$operation message", ['messageId' => $message->getUid(), 'mailboxId' => $message->getMailboxId()]);
			$mappedUids[spl_object_id($message)] = $message->getUid();
		}
				
		// update remote store
		$mutatedMessages = match ($operation) {
			'move' => $this->protocolFactory
				->messageConnector($account)
				->moveMessages($account, $trashMailbox, $sourceMailbox, ...$messages),
			'delete' => $this->protocolFactory
				->messageConnector($account)
				->deleteMessages($account, $sourceMailbox, ...$messages),
			default => throw new ServiceException('Invalid operation'),
		};

		// update local store
		if ($operation === 'move') {
			$this->dbMessageMapper->updateBulk($account, false, ...$mutatedMessages);
		}
		if ($operation === 'delete') {
			$mutatedUids = array_map(static fn (Message $message): int => $message->getUid(), $mutatedMessages);
			$this->dbMessageMapper->deleteByUid($sourceMailbox, ...$mutatedUids);
		}

		// dispatch events
		foreach ($mutatedMessages as $mutatedMessage) {
			$this->eventDispatcher->dispatchTyped(new MessageDeletedEvent($account, $sourceMailbox, $mappedUids[spl_object_id($mutatedMessage)]));
		}
	}

	public function flagMessages(Account $account, Mailbox $mailbox, string $flag, bool $value, Message ...$messages): void {
		if ($messages === []) {
			return;
		}
		// update remote store
		$mutatedMessages = $this->protocolFactory
			->messageConnector($account)
			->flagMessages($account, $mailbox, $flag, $value, ...$messages);

		// update local store
		$this->dbMessageMapper->updateBulk($account, true, ...$mutatedMessages);

		// dispatch events
		foreach ($mutatedMessages as $message) {
			$this->eventDispatcher->dispatchTyped(new MessageFlaggedEvent($account, $mailbox, $message->getUid(), $flag, $value));
		}
	}

	public function tagMessages(Account $account, Mailbox $mailbox, Tag $tag, bool $value, Message ...$messages): void {
		if ($messages === []) {
			return;
		}
		// update remote store
		$mutatedMessages = $this->protocolFactory
			->messageConnector($account)
			->tagMessages($account, $mailbox, $tag, $value, ...$messages);

		// update local store
		$this->dbMessageMapper->updateBulk($account, true, ...$mutatedMessages);
	}

	public function markFolderAsRead(Account $account, Mailbox $mailbox): void {
		// find all messages in mailbox with their remote ids
		$messages = $this->dbMessageMapper->findByUids($mailbox, $this->dbMessageMapper->findAllUids($mailbox));
		if ($messages === []) {
			return;
		}
		$this->flagMessages($account, $mailbox, 'seen', true, ...$messages);
	}

	public function getThread(Account $account, string $threadRootId): array {
		return $this->dbMessageMapper->findThread($account, $threadRootId);
	}

	/**
	 * Finds all messages in the thread of the given thread root id
	 *
	 * @return array<string, Message> array of messages in the thread, keyed by remote id
	 */
	public function fetchThread(Account $account, Mailbox $mailbox, string $threadRootId): array {
		$mailAccount = $account->getMailAccount();
		$messageInTrash = $mailbox->getId() === $mailAccount->getTrashMailboxId();
		$threadMessages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash,
		);

		// group message uids by mailbox
		$uids = [];
		foreach ($threadMessages as $threadMessage) {
			$uids[$threadMessage['mailboxName']][] = $threadMessage['messageUid'];
		}
		unset($threadMessages);

		// retrieve messages from local store
		$messages = [];
		foreach ($uids as $mailboxName => $messageUids) {
			$sourceMailbox = $mailboxes[$mailboxName] ??= $this->mailboxMapper->find($account, $mailboxName);
			$sourceMessages = $this->dbMessageMapper->findByUids($sourceMailbox, $messageUids);
			$messages = array_merge($messages, $sourceMessages);
		}

		return $messages;
	}

	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array {
		if ($srcAccount->getId() !== $dstAccount->getId()) {
			throw new ServiceException('It is not possible to move across accounts yet');
		}

		$messages = $this->fetchThread($srcAccount, $srcMailbox, $threadRootId);
		if ($messages === []) {
			return [];
		}

		$this->moveMessages($srcAccount, $dstMailbox, $srcMailbox, ...$messages);

		return $mutatedUids;
	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void {
		if ($$account->getMailAccount()->getTrashMailboxId() === null) {
			throw new TrashMailboxNotSetException();
		}

		$messages = $this->fetchThread($account, $sourceMailbox, $threadRootId);
		if ($messages === []) {
			return;
		}

		$this->deleteMessages($account, $sourceMailbox, ...$messages);
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
			->fetchAttachments($account, $mailbox, $message);
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
	public function getMailAttachment(Account $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment {
		return $this->protocolFactory
			->messageConnector($account)
			->fetchAttachment($account, $mailbox, $message, $attachmentId);
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
	 * Check IMAP server for support for PERMANENTFLAGS
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @return boolean
	 */
	public function isPermflagsEnabled(Account $account, Mailbox $mailbox): bool {
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
				->tagMessages($account, $tag, false, ...$messages);
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
