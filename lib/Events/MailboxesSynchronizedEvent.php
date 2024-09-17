<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class MailboxesSynchronizedEvent extends Event {
	public function __construct(private MailAccount $account) {
		parent::__construct();
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}
}
