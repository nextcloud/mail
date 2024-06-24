<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;

interface IExtractor {
	/**
	 * Initialize any data that is used for all messages and return whether the
	 * extractor is applicable for this account
	 *
	 * @param Account $account
	 * @param Mailbox[] $incomingMailboxes
	 * @param Mailbox[] $outgoingMailboxes
	 * @param Message[] $messages
	 */
	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void;

	/**
	 * Return the feature value for the given message
	 *
	 * @param Message $message
	 *
	 * @return float[]
	 */
	public function extract(Message $message): array;
}
