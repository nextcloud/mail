<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;

class NewMessagesSynchronized extends Event {

	/** @var Mailbox */
	private $mailbox;

	/** @var array|Message[] */
	private $messages;

	/**
	 * @param Mailbox $mailbox
	 * @param Message[] $messages
	 */
	public function __construct(private MailAccount $account,
		Mailbox $mailbox,
		array $messages) {
		parent::__construct();
		$this->mailbox = $mailbox;
		$this->messages = $messages;
	}

	public function getAccount(): MailAccount {
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
