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
	/** @var Account */
	private $account;

	public function __construct(
		Account $account,
		private LocalMessage $localMessage,
	) {
		parent::__construct();
		$this->account = $account;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getLocalMessage(): LocalMessage {
		return $this->localMessage;
	}
}
