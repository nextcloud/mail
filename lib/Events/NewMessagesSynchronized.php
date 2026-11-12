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

class NewMessagesSynchronized extends Event {
	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message[] $messages
	 */
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private array $messages,
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getMailbox(): Mailbox {
		return $this->mailbox;
	}

	/**
	 * @return Message[]
	 */
	public function getMessages() {
		return $this->messages;
	}
}
