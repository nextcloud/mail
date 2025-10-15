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

class MessageDeletedEvent extends Event {
	/** @var Account */
	private $account;

	/** @var Mailbox */
	private $mailbox;

	/** @var int */
	private $messageId;

	public function __construct(Account $account,
		Mailbox $mailbox,
		int $messageId) {
		parent::__construct();
		$this->account = $account;
		$this->mailbox = $mailbox;
		$this->messageId = $messageId;
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
}
