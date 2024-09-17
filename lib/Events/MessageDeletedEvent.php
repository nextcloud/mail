<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCP\EventDispatcher\Event;

class MessageDeletedEvent extends Event {
	/** @var Mailbox */
	private $mailbox;

	/** @var int */
	private $messageId;

	public function __construct(private MailAccount $account,
		Mailbox $mailbox,
		int $messageId) {
		parent::__construct();
		$this->mailbox = $mailbox;
		$this->messageId = $messageId;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getMailbox(): Mailbox {
		return $this->mailbox;
	}

	public function getMessageId(): int {
		return $this->messageId;
	}
}
