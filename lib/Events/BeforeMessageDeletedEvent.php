<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCP\EventDispatcher\Event;

class BeforeMessageDeletedEvent extends Event {
	/** @var Account */
	private $account;

	/** @var string */
	private $folderId;

	/** @var int */
	private $messageId;

	public function __construct(Account $account, string $mailbox, int $messageId) {
		parent::__construct();
		$this->account = $account;
		$this->folderId = $mailbox;
		$this->messageId = $messageId;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getFolderId(): string {
		return $this->folderId;
	}

	public function getMessageId(): int {
		return $this->messageId;
	}
}
