<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail;

use OCA\Mail\Db\MailAccount;

class Account {
	public function __construct(private MailAccount $account) {
	}

	public function getMailAccount(): MailAccount {
		return $this->account;
	}

}
