<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;

interface IDkimService {
	public function validate(Account $account, Mailbox $mailbox, int $id): bool;
	public function getCached(Account $account, Mailbox $mailbox, int $id): ?bool;
}
