<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Model\NewMessageData;
use OCP\EventDispatcher\Event;

class DraftSavedEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly ?\OCA\Mail\Model\NewMessageData $newMessageData = null,
		private readonly ?\OCA\Mail\Db\Message $draft = null
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getNewMessageData(): ?NewMessageData {
		return $this->newMessageData;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
