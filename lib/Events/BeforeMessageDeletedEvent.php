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
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly string $folderId,
		private readonly int $messageId
	) {
		parent::__construct();
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
