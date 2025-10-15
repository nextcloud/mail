<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use OCA\Mail\Db\Mailbox;

class MailboxNotCachedException extends ClientException {
	public static function from(Mailbox $mailbox): self {
		return new self("mailbox {$mailbox->getId()} is not cached");
	}
}
