<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;

class SendHandler extends AHandler {
	public function __construct(
		private IMailTransmission $transmission,
	) {
	}

	public function process(Account $account, LocalMessage $localMessage): LocalMessage {
		if ($localMessage->getStatus() === LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL
			|| $localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage);
		}

		$this->transmission->sendMessage($account, $localMessage);

		if ($localMessage->getStatus() === LocalMessage::STATUS_RAW || $localMessage->getStatus() === null) {
			return $this->processNext($account, $localMessage);
		}
		// Something went wrong during the sending
		return $localMessage;
	}
}
