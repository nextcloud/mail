<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\NewMessageData;

interface IMailTransmission {
	/**
	 * Send a new message or reply to an existing one
	 *
	 * @throws SentMailboxNotSetException
	 * @throws ServiceException
	 */
	public function sendMessage(Account $account, LocalMessage $localMessage): void;

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function saveLocalDraft(Account $account, LocalMessage $message): void;

	/**
	 * Save a message draft
	 *
	 *
	 *
	 * @throws ClientException if no drafts mailbox is configured
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, ?Message $previousDraft = null): array;

	/**
	 * Send a mdn message
	 *
	 * @param Message $message the message to send an mdn for
	 * @throws ServiceException
	 */
	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void;
}
