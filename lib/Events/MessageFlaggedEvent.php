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

class MessageFlaggedEvent extends Event {
	/** @var Mailbox */
	private $mailbox;

	/** @var int */
	private $uid;

	/** @var string */
	private $flag;

	/** @var bool */
	private $set;

	public function __construct(private MailAccount $account,
		Mailbox $mailbox,
		int $uid,
		string $flag,
		bool $set) {
		parent::__construct();
		$this->mailbox = $mailbox;
		$this->uid = $uid;
		$this->flag = $flag;
		$this->set = $set;
	}

	public function getAccount(): MailAccount {
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
