<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use Psr\Log\LoggerInterface;

interface IMailboxConnector {

	/**
	 * Synchronise the list of mailboxes for the given account.
	 */
	public function syncAccount(Account $account, bool $force = false): void;

	/**
	 * Refresh cached statistics (total / unseen) for a single mailbox.
	 */
	public function syncMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 * Create a new mailbox on the remote server and persist it locally.
	 *
	 * @param string[] $specialUse
	 */
	public function createMailbox(Account $account, string $name, array $specialUse = []): Mailbox;

	/**
	 * Rename an existing mailbox on the remote server.
	 */
	public function renameMailbox(Account $account, Mailbox $mailbox, string $newName): Mailbox;

	/**
	 * Delete a mailbox on the remote server and remove it locally.
	 */
	public function deleteMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 * Subscribe or unsubscribe a mailbox.
	 */
	public function subscribeMailbox(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox;
}
