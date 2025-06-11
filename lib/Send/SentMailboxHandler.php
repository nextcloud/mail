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

class SentMailboxHandler extends AHandler {
	#[\Override]
	public function process(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage {
		if ($account->getMailAccount()->getSentMailboxId() === null) {
			$localMessage->setStatus(LocalMessage::STATUS_NO_SENT_MAILBOX);
			return $localMessage;
		}
		return $this->processNext($account, $localMessage, $client);
	}
}
