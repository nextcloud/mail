<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class MessageSentEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly LocalMessage $localMessage,
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getLocalMessage(): LocalMessage {
		return $this->localMessage;
	}
}
