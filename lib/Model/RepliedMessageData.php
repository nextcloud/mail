<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Model;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;

/**
 * An immutable DTO that holds information about a message that is replied to
 *
 * @psalm-immutable
 */
class RepliedMessageData {
	private MailAccount $account;

	/** @var Message */
	private $message;

	public function __construct(MailAccount $account, Message  $message) {
		$this->account = $account;
		$this->message = $message;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getMessage(): Message {
		return $this->message;
	}
}
