<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Send;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use Psr\Log\LoggerInterface;

class SentMailboxHandler extends AHandler {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function process(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage {
		if ($account->getMailAccount()->getSentMailboxId() === null) {
			// Blocking the send here strands the message in the outbox forever
			// (https://github.com/nextcloud/mail/issues/10546). CopySentMessageHandler
			// marks the message after the SMTP send instead.
			$this->logger->warning('No sent mailbox configured for account {accountId}, sending without archiving a copy', [
				'accountId' => $account->getMailAccount()->getId(),
			]);
		}
		return $this->processNext($account, $localMessage, $client);
	}
}
