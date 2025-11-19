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

class MessageFlaggedEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly \OCA\Mail\Db\Mailbox $mailbox,
		private readonly int $uid,
		private readonly string $flag,
		private readonly bool $set
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
}
