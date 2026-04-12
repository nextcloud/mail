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
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\Quota;

interface IMessageConnector {

	/**
	 * Perform a differential sync for the given mailbox.
	 */
	public function syncMessages(Account $account, Mailbox $mailbox, bool $force = false): SyncResult;

	/**
	 * Fetch a single message envelope (and optionally its body).
	 */
	public function fetchMessage(Account $account, Mailbox $mailbox, int $uid, bool $loadBody = false): IMAPMessage;

	/**
	 * Fetch the raw RFC 5322 source of a message.
	 */
	public function fetchMessageBody(Account $account, Mailbox $mailbox, int $uid): ?string;

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
	 * Set or unset a flag on a message.
	 */
	public function flagMessage(Account $account, Mailbox $mailbox, int $uid, string $flag, bool $value): void;

	/**
	 * Move a message to a different mailbox.
	 *
	 * @return int|null The new UID in the destination mailbox, if known.
	 */
	public function moveMessage(Account $account, string $sourceMailbox, int $uid, string $destMailbox): ?int;

	/**
	 * Permanently delete a message.
	 */
	public function deleteMessage(Account $account, Mailbox $mailbox, int $uid): void;

	/**
	 * Mark all messages in a mailbox as read.
	 */
	public function markAllRead(Account $account, Mailbox $mailbox): void;

	/**
	 * Get the quota for the account.
	 */
	public function getQuota(Account $account): ?Quota;

	/**
	 * Check whether permanent flags are enabled for a mailbox.
	 */
	public function isPermflagsEnabled(Account $account, string $mailbox): bool;
}
