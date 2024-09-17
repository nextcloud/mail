<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;
use OCA\Mail\Model\NewMessageData;
use OCP\EventDispatcher\Event;

class SaveDraftEvent extends Event {
	/** @var NewMessageData */
	private $newMessageData;

	/** @var Message|null */
	private $draft;

	public function __construct(private MailAccount $account,
		NewMessageData $newMessageData,
		?Message $draft) {
		parent::__construct();
		$this->newMessageData = $newMessageData;
		$this->draft = $draft;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getNewMessageData(): NewMessageData {
		return $this->newMessageData;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
