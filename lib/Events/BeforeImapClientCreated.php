<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCP\EventDispatcher\Event;

class BeforeImapClientCreated extends Event {
	/** @var Account */
	private $account;

	public function __construct(Account $account) {
		parent::__construct();
		$this->account = $account;
	}

	/**
	 * @return Account
	 */
	public function getAccount(): Account {
		return $this->account;
	}
}
