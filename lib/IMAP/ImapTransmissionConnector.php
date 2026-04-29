<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use OCA\Mail\Account;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Service\MailTransmission;

class ImapTransmissionConnector implements ITransmissionConnector {
	public function __construct(
		private MailTransmission $mailTransmission,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $imapClientFactory,
	) {
	}

	#[\Override]
	public function sendMessage(Account $account, LocalMessage $message): void {
		$this->mailTransmission->sendMessage($account, $message);
	}

	#[\Override]
	public function saveDraft(Account $account, LocalMessage $message): void {
		$this->mailTransmission->saveLocalDraft($account, $message);
	}

	#[\Override]
	public function saveRawMessageToMailbox(Account $account, Mailbox $mailbox, string $raw, array $flags = []): ?int {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->save($client, $mailbox, $raw, $flags);
		} finally {
			$client->logout();
		}
	}
}
