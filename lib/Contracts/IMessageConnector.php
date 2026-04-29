<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\Quota;
use Psr\Log\LoggerInterface;

interface IMessageConnector {

	/**
	 * Synchronize all relevant mailboxes for the account.
	 */
	public function syncAccount(Account $account, bool $force = false): void;

	/**
	 * Perform a differential sync for the given mailbox.
	 */
	public function syncMessages(Account $account, Mailbox $mailbox, bool $force = false): SyncResult;

	/**
	 * Synchronize a single mailbox with protocol-specific options.
	 *
	 * @param int[]|null $knownUids
	 */
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult;

	/**
	 * Clear the local cache for a mailbox.
	 */
	public function clearCache(Account $account, Mailbox $mailbox): void;

	/**
	 * Repair the local cache for a mailbox.
	 */
	public function repairSync(Account $account, Mailbox $mailbox, LoggerInterface $logger): void;

	/**
	 * Fetch a single message envelope (and optionally its body).
	 */
	public function fetchMessage(Account $account, Mailbox $mailbox, int $uid, bool $loadBody = false): IMAPMessage;

	/**
	 * Fetch the raw RFC 5322 source of a message.
	 */
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, int $uid): ?string;

	/**
	 * Fetch all attachments for a message.
	 *
	 * @return Attachment[]
	 */
	public function fetchAttachments(Account $account, Mailbox $mailbox, int $uid): array;

	/**
	 * Fetch a single attachment by its ID.
	 */
	public function fetchAttachment(Account $account, Mailbox $mailbox, int $uid, string $attachmentId): Attachment;

	/**
	 * Set or unset a flag on a messages.
	 */
	public function flagMessages(Account $account, string $flag, bool $value, Message ...$messages): void;

	/** 
	 * Set or unset a tags on all messages in a thread.
	 */
	public function tagMessages(Account $account, Tag $tag, bool $value, Message ...$messages): void;

	/**
	 * Move a message to a different mailbox.
	 *
	 * @return int|null The new UID in the destination mailbox, if known.
	 */
	public function moveMessage(Account $account, string $sourceMailbox, int $uid, string $destMailbox): ?int;

	/**
	 * Move all messages in a thread to a different mailbox.
	 *
	 * @return int[] The new UIDs in the destination mailbox, when known.
	 */
	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array;

	/**
	 * Permanently delete a message.
	 */
	public function deleteMessage(Account $account, Mailbox $mailbox, int $uid): void;

	/**
	 * Delete all messages in a thread.
	 */
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void;

	/**
	 * Clear all messages from a mailbox.
	 */
	public function clearMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 * Get the quota for the account.
	 */
	public function getQuota(Account $account): ?Quota;

	/**
	 * Check whether permanent flags are enabled for a mailbox.
	 */
	public function isPermflagsEnabled(Account $account, string $mailbox): bool;
}
