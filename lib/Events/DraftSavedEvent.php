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
	/** @var Account */
	private $account;

	/** @var NewMessageData|null */
	private $newMessageData;

	/** @var Message|null */
	private $draft;

	public function __construct(Account $account,
		?NewMessageData $newMessageData = null,
		?Message $draft = null) {
		parent::__construct();
		$this->account = $account;
		$this->newMessageData = $newMessageData;
		$this->draft = $draft;
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
