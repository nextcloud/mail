<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;

interface ITransmissionConnector {

	/**
	 * Send a composed message and file a copy in the sent mailbox.
	 */
	public function sendMessage(Account $account, LocalMessage $message, Mailbox $sentMailbox): void;

	/**
	 * Send a Message Disposition Notification (read receipt).
	 */
	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void;

	/**
	 * Store a message in a mailbox.
	 *
	 * @param string[] $flags JMAP-style keywords to set on the stored message (e.g. ['$draft']).
	 *                        Each connector maps these to its native flag format internally.
	 */
	public function saveMessage(Account $account, Mailbox $mailbox, LocalMessage $message, array $flags = []): void;
}
