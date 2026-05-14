<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

class MessageDeletedEvent extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private int $messageId,
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getMailbox(): Mailbox {
		return $this->mailbox;
	}

	public function getMessageId(): int {
		return $this->messageId;
	}

	public function getWebhookSerializable(): array {
		return [
			'accountId' => $this->account->getId(),
			'mailboxId' => $this->mailbox->getId(),
			'messageId' => $this->messageId,
		];
	}
}
