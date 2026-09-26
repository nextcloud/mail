<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

class MessageFlaggedEvent extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private Message $message,
		private string $flag,
		private bool $set,
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getMailbox(): Mailbox {
		return $this->mailbox;
	}

	public function getMessage(): Message {
		return $this->message;
	}

	public function getUid(): int {
		return $this->message->getUid();
	}

	public function getFlag(): string {
		return $this->flag;
	}

	public function isSet(): bool {
		return $this->set;
	}

	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'accountId' => $this->account->getId(),
			'flag' => $this->flag,
			'mailboxId' => $this->mailbox->getId(),
			'messageId' => $this->message->getId(),
			'set' => $this->set,
			'uid' => $this->message->getUid(),
		];
	}
}
