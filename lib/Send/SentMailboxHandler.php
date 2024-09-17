<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;

class SentMailboxHandler extends AHandler {
	public function process(MailAccount $account, LocalMessage $localMessage): LocalMessage {
		if ($account->getSentMailboxId() === null) {
			$localMessage->setStatus(LocalMessage::STATUS_NO_SENT_MAILBOX);
			return $localMessage;
		}
		return $this->processNext($account, $localMessage);
	}
}
