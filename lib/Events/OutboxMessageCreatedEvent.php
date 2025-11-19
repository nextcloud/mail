<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class OutboxMessageCreatedEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly \OCA\Mail\Db\Message $draft
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
