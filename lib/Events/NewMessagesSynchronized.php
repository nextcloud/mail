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
	/** @var Account */
	private $account;

	/** @var Mailbox */
	private $mailbox;

	/** @var array|Message[] */
	private $messages;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param Message[] $messages
	 */
	public function __construct(Account $account,
		Mailbox $mailbox,
		array $messages) {
		parent::__construct();
		$this->account = $account;
		$this->mailbox = $mailbox;
		$this->messages = $messages;
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
