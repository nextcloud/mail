<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCP\EventDispatcher\Event;

class BeforeMessageDeletedEvent extends Event {
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private int $uid,
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getMailbox(): Mailbox {
		return $this->mailbox;
	}

	public function getFolderId(): string {
		return $this->mailbox->getName();
	}

	public function getUid(): int {
		return $this->uid;
	}
}
