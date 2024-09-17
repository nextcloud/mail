<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Contracts;

use Horde_Imap_Client_Socket;
use OCA\Mail\Attachment;
use OCA\Mail\Db\MailAccount;
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
	 * @param string $uid
	 * @param int $id
	 *
	 * @return Mailbox
	 *
	 * @throws ClientException
	 */
	public function getMailbox(string $uid, int $id): Mailbox;

	/**
	 * @return Mailbox[]
	 * @throws ServiceException
	 */
	public function getMailboxes(MailAccount $account): array;

	/**
	 * @param string $name
	 *
	 * @return Mailbox
	 *
	 * @throws ServiceException
	 */
	public function createMailbox(MailAccount $account, string $name): Mailbox;

	/**
	 * @param Mailbox $mailbox
	 * @param $uid
	 *
	 * @return int|null
	 */
	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int;

	/**
	 * @param string $uid
	 * @param int $id
	 *
	 * @return Message
	 *
	 * @throws DoesNotExistException
	 */
	public function getMessage(string $uid, int $id): Message;

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Horde_Imap_Client_Socket $client,
		MailAccount $account,
		string $mailbox,
		int $uid): ?string;

	/**
	 * @param Horde_Imap_Client_Socket $client
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @param bool $loadBody
	 *
	 * @return IMAPMessage
	 *
	 * @throws ServiceException
	 */
	public function getImapMessage(Horde_Imap_Client_Socket $client,
		MailAccount $account,
		Mailbox $mailbox,
		int $uid,
		bool $loadBody = false): IMAPMessage;

	/**
	 * @param string $threadRootId thread root id
	 *
	 * @return Message[]
	 */
	public function getThread(MailAccount $account, string $threadRootId): array;

	/**
	 * @param string $sourceFolderId
	 * @param int $uid
	 * @param string $destFolderId
	 * @return int the new UID
	 *
	 * @throws ServiceException
	 */
	public function moveMessage(MailAccount $sourceAccount,
		string $sourceFolderId,
		int $uid,
		MailAccount $destinationAccount,
		string $destFolderId): int;

	/**
	 * @param string $mailboxId
	 * @param int $messageUid
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteMessage(MailAccount $account, string $mailboxId, int $messageUid): void;

	/**
	 * @param Mailbox $mailbox
	 * @param int $messageUid
	 * @param Horde_Imap_Client_Socket $client The caller is responsible to close the client.
	 *
	 * @throws ServiceException
	 * @throws ClientException
	 * @throws TrashMailboxNotSetException If no trash folder is configured for the given account.
	 */
	public function deleteMessageWithClient(
		MailAccount $account,
		Mailbox $mailbox,
		int $messageUid,
		Horde_Imap_Client_Socket $client,
	): void;

	/**
	 * Mark all messages of a folder as read
	 *
	 * @param Mailbox $mailbox
	 */
	public function markFolderAsRead(MailAccount $account, Mailbox $mailbox): void;

	/**
	 * @param string $mailbox
	 * @param int $uid
	 * @param string $flag
	 * @param bool $value
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function flagMessage(MailAccount $account, string $mailbox, int $uid, string $flag, bool $value): void;

	/**
	 * @param string $mailbox
	 * @param Message $message
	 * @param Tag $tag
	 * @param bool $value
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function tagMessage(MailAccount $account, string $mailbox, Message $message, Tag $tag, bool $value): void;

	/**
	 * @return Quota|null
	 */
	public function getQuota(MailAccount $account): ?Quota;

	/**
	 * Rename a mailbox and get the new (cached) version
	 *
	 * @param Mailbox $mailbox
	 * @param string $name
	 *
	 * @return Mailbox
	 *
	 * @throw ServiceException
	 */
	public function renameMailbox(MailAccount $account, Mailbox $mailbox, string $name): Mailbox;

	/**
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function deleteMailbox(MailAccount $account, Mailbox $mailbox): void;

	/**
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function clearMailbox(MailAccount $account, Mailbox $mailbox): void;

	/**
	 * @param Mailbox $mailbox
	 * @param bool $subscribed
	 *
	 * @return Mailbox
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function updateSubscription(MailAccount $account,
		Mailbox $mailbox,
		bool $subscribed): Mailbox;

	/**
	 * @param Mailbox $mailbox
	 * @param bool $syncInBackground
	 *
	 * @return Mailbox
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function enableMailboxBackgroundSync(Mailbox $mailbox,
		bool $syncInBackground): Mailbox;

	/**
	 * @param Mailbox $mailbox
	 * @param Message $message
	 * @return Attachment[]
	 */
	public function getMailAttachments(MailAccount $account, Mailbox $mailbox, Message $message) : array;

	/**
	 * @param Mailbox $mailbox
	 * @param Message $message
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getMailAttachment(MailAccount $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment;

	/**
	 * @param string $imapLabel
	 * @param string $userId
	 * @return Tag
	 * @throws ClientException
	 */
	public function getTagByImapLabel(string $imapLabel, string $userId): Tag;

	/**
	 * Check IMAP server for support for PERMANENTFLAGS
	 */
	public function isPermflagsEnabled(Horde_Imap_Client_Socket $client, MailAccount $account, string $mailbox): bool;

	/**
	 * Create a mail tag
	 *
	 * @param string $userId
	 * @param string $displayName
	 * @param string $color
	 * @return Tag
	 * @throws ClientException if display name does not work as imap label
	 */
	public function createTag(string $displayName, string $color, string $userId): Tag;

	/**
	 * Update a mail tag
	 *
	 * @param string $userId
	 * @param int $id
	 * @param string $displayName
	 * @param string $color
	 * @return Tag
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
	public function deleteTagForAccount(int $id, string $userId, Tag $tag, MailAccount $account): void;

	/**
	 * @param Mailbox $srcMailbox
	 * @param Mailbox $dstMailbox
	 * @param string $threadRootId
	 * @return int[] the new UIDs
	 * @throws ServiceException
	 */
	public function moveThread(MailAccount $srcAccount, Mailbox $srcMailbox, MailAccount $dstAccount, Mailbox $dstMailbox, string $threadRootId): array;

	/**
	 * @param Mailbox $mailbox
	 * @param string $threadRootId
	 * @return void
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteThread(MailAccount $account, Mailbox $mailbox, string $threadRootId): void;

	/**
	 * @param string $messageId
	 * @return Message[]
	 */
	public function getByMessageId(MailAccount $account, string $messageId): array;
}
