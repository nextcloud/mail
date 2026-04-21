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

interface ITransmissionConnector {

	/**
	 * Send a composed message.
	 */
	public function sendMessage(Account $account, LocalMessage $message): void;

	/**
	 * Save a message as a draft.
	 */
	public function saveDraft(Account $account, LocalMessage $message): void;

	/**
	 * Store a raw RFC 5322 message into a mailbox.
	 *
	 * @param string[] $flags Flags to set on the stored message.
	 * @return int|null The UID of the stored message, if known.
	 */
	public function saveRawMessageToMailbox(Account $account, Mailbox $mailbox, string $raw, array $flags = []): ?int;
}
