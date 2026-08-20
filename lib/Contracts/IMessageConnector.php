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
use OCA\Mail\Service\Search\SearchQuery;
use Psr\Log\LoggerInterface;

interface IMessageConnector {

	/**
	 * Synchronize all relevant mailboxes for the account.
	 */
	public function syncAll(Account $account, bool $force = false): void;

	/**
	 * Synchronize a single mailbox with protocol-specific options.
	 *
	 * @param int[]|null $knownUids
	 */
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult;

	/**
	 * Fetch a single message envelope (and optionally its body).
	 *
	 * @return IMAPMessage[] The fetched messages
	 */
	public function fetchMessages(Account $account, Mailbox $mailbox, bool $loadBody = false, Message ...$messages): array;

	/**
	 * Find matching message UIDs in a mailbox.
	 *
	 * @return int[]
	 */
	public function findMessages(Account $account, Mailbox $mailbox, SearchQuery $searchQuery): array;

	/**
	 * Fetch the raw RFC 5322 source of a message.
	 */
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, Message $message): ?string;

	/**
	 * Fetch all attachments for a message.
	 *
	 * @return Attachment[]
	 */
	public function fetchAttachments(Account $account, Mailbox $mailbox, Message $message): array;

	/**
	 * Fetch a single attachment by its ID.
	 */
	public function fetchAttachment(Account $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment;

	/**
	 * Move a message to a different mailbox.
	 *
	 * @return Message[] The fetched messages
	 */
	public function moveMessages(Account $account, Mailbox $targetMailbox, Mailbox $sourceMailbox, Message ...$messages): array;

	/**
	 * Permanently delete a message.
	 *
	 * @return Message[] The deleted messages
	 */
	public function deleteMessages(Account $account, Mailbox $mailbox, Message ...$message): array;

	/**
	 * Set or unset a flag on a messages.
	 *
	 * @return Message[] The mutated messages
	 */
	public function flagMessages(Account $account, Mailbox $mailbox, string $flag, bool $value, Message ...$messages): array;

	/**
	 * Set or unset a tags on all messages in a thread.
	 *
	 * @return Message[] The mutated messages
	 */
	public function tagMessages(Account $account, Mailbox $mailbox, Tag $tag, bool $value, Message ...$messages): array;

	/**
	 * Get the quota for the account.
	 */
	public function getQuota(Account $account): ?Quota;

	/**
	 * Clear the local cache for a mailbox.
	 */
	public function clearCache(Account $account, Mailbox $mailbox): void;

	/**
	 * Repair the local cache for a mailbox.
	 */
	public function repairSync(Account $account, Mailbox $mailbox): void;

	/**
	 * Check whether permanent flags are enabled for a mailbox.
	 */
	public function isPermflagsEnabled(Account $account, Mailbox $mailbox): bool;
}
