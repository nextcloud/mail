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

class MessageFlaggedEvent extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private int $uid,
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

	public function getUid(): int {
		return $this->uid;
	}

	public function getFlag(): string {
		return $this->flag;
	}

	public function isSet(): bool {
		return $this->set;
	}

	public function getWebhookSerializable(): array {
		return [
			'accountId' => $this->account->getId(),
			'mailboxId' => $this->mailbox->getId(),
			'messageUid' => $this->uid,
			'flag' => $this->flag,
			'set' => $this->set,
		];
	}
}
