<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\MailAccount;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class MessageSentEvent extends Event {
	public function __construct(private MailAccount $account,
		private LocalMessage $localMessage) {
		parent::__construct();
		$this->account = $account;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getLocalMessage(): LocalMessage {
		return $this->localMessage;
	}
}
