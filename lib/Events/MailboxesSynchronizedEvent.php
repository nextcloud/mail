<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class MailboxesSynchronizedEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}
}
