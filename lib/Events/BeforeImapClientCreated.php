<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCP\EventDispatcher\Event;
class BeforeImapClientCreated extends Event {
	private MailAccount $account;

	public function __construct(MailAccount $account) {
		parent::__construct();
		$this->account = $account;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}
}
