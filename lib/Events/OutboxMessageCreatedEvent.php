<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class OutboxMessageCreatedEvent extends Event {
	/** @var Message */
	private $draft;

	public function __construct(private MailAccount $account,
		Message $draft) {
		parent::__construct();
		$this->draft = $draft;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
