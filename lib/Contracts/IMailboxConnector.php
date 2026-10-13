<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;

interface IMailboxConnector {

	/**
	 * Synchronize the list of mailboxes for the given account.
	 *
	 * @throws ServiceException
	 */
	public function syncAll(Account $account, bool $force = false): void;

	/**
	 * Refresh cached statistics (total / unseen) for a single mailbox.
	 *
	 * @throws ServiceException
	 */
	public function syncOne(Account $account, Mailbox $mailbox): void;

	/**
	 * Create a new mailbox on the remote server and persist it locally.
	 *
	 * @param string[] $specialUse
	 *
	 * @throws ServiceException
	 */
	public function create(Account $account, string $name, array $specialUse = []): Mailbox;

	/**
	 * Rename an existing mailbox on the remote server.
	 *
	 * @throws ServiceException
	 */
	public function rename(Account $account, Mailbox $mailbox, string $newName): Mailbox;

	/**
	 * Delete a mailbox on the remote server and remove it locally.
	 *
	 * @throws ServiceException
	 */
	public function delete(Account $account, Mailbox $mailbox): void;

	/**
	 * Subscribe or unsubscribe a mailbox.
	 *
	 * @throws ServiceException
	 */
	public function subscribe(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox;
}
