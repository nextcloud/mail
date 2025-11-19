<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Contracts;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Quota;
use OCP\AppFramework\Db\DoesNotExistException;

interface IMailManager {
	/**
	 *
	 * @throws ClientException
	 */
	public function getMailbox(string $uid, int $id): Mailbox;

	/**
	 *
	 * @return Mailbox[]
	 * @throws ServiceException
	 */
	public function getMailboxes(Account $account): array;

	/**
	 *
	 * @throws ServiceException
	 */
	public function createMailbox(Account $account, string $name): Mailbox;

	/**
	 * @param $uid
	 *
	 */
	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int;

	/**
	 *
	 * @throws DoesNotExistException
	 */
	public function getMessage(string $uid, int $id): Message;

	/**
	 *
	 * @return string
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Horde_Imap_Client_Socket $client,
		Account $account,
		string $mailbox,
		int $uid): ?string;

	/**
	 *
	 *
	 * @throws ServiceException
	 */
	public function getImapMessage(Horde_Imap_Client_Socket $client,
		Account $account,
		Mailbox $mailbox,
		int $uid,
		bool $loadBody = false): IMAPMessage;

	/**
	 * @param string $threadRootId thread root id
	 * @return Message[]
	 */
	public function getThread(Account $account, string $threadRootId): array;

	/**
	 * @return ?int the new UID (or null if it couldn't be determined)
	 *
	 * @throws ServiceException
	 */
	public function moveMessage(Account $sourceAccount,
		string $sourceFolderId,
		int $uid,
		Account $destinationAccount,
		string $destFolderId): ?int;

	/**
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteMessage(Account $account, string $mailboxId, int $messageUid): void;

	/**
	 * @param Horde_Imap_Client_Socket $client The caller is responsible to close the client.
	 * @throws ServiceException
	 * @throws ClientException
	 * @throws TrashMailboxNotSetException If no trash folder is configured for the given account.
	 */
	public function deleteMessageWithClient(
		Account $account,
		Mailbox $mailbox,
		int $messageUid,
		Horde_Imap_Client_Socket $client,
	): void;

	/**
	 * Mark all messages of a folder as read
	 */
	public function markFolderAsRead(Account $account, Mailbox $mailbox): void;

	/**
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function flagMessage(Account $account, string $mailbox, int $uid, string $flag, bool $value): void;

	/**
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function tagMessage(Account $account, string $mailbox, Message $message, Tag $tag, bool $value): void;

	public function getQuota(Account $account): ?Quota;

	/**
	 * Rename a mailbox and get the new (cached) version
	 *
	 *
	 *
	 * @throw ServiceException
	 */
	public function renameMailbox(Account $account, Mailbox $mailbox, string $name): Mailbox;

	/**
	 *
	 * @throws ServiceException
	 */
	public function deleteMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 *
	 * @throws ServiceException
	 */
	public function clearMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function updateSubscription(Account $account,
		Mailbox $mailbox,
		bool $subscribed): Mailbox;

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function enableMailboxBackgroundSync(Mailbox $mailbox,
		bool $syncInBackground): Mailbox;

	/**
	 * @return Attachment[]
	 */
	public function getMailAttachments(Account $account, Mailbox $mailbox, Message $message) : array;

	public function getMailAttachment(Account $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment;

	/**
	 * @throws ClientException
	 */
	public function getTagByImapLabel(string $imapLabel, string $userId): Tag;

	/**
	 * Check IMAP server for support for PERMANENTFLAGS
	 */
	public function isPermflagsEnabled(Horde_Imap_Client_Socket $client, Account $account, string $mailbox): bool;

	/**
	 * Create a mail tag
	 *
	 * @throws ClientException if display name does not work as imap label
	 */
	public function createTag(string $displayName, string $color, string $userId): Tag;

	/**
	 * Update a mail tag
	 *
	 * @throws ClientException if the given tag does not exist
	 */
	public function updateTag(int $id, string $displayName, string $color, string $userId): Tag;

	/**
	 * Delete a mail tag
	 *
	 * @throws ClientException
	 */
	public function deleteTag(int $id, string $userId, array $accounts): Tag;

	/**
	 * Delete message Tags and untagged messages on Imap
	 *
	 * @throws ClientException
	 */
	public function deleteTagForAccount(int $id, string $userId, Tag $tag, Account $account): void;
	/**
	 * @return int[] the new UIDs (not guaranteed to have an entry for each message of the thread)
	 * @throws ServiceException
	 */
	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array;

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void;

	/**
	 * @return Message[]
	 */
	public function getByMessageId(Account $account, string $messageId): array;
}
