<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use OCA\Mail\Db\Mailbox;
use OCP\AppFramework\Http;

class MailboxLockedException extends ClientException {
	public static function from(Mailbox $mailbox): self {
		return new self($mailbox->getId() . ' is already being synced');
	}

	#[\Override]
	public function getHttpCode(): int {
		return Http::STATUS_CONFLICT;
	}
}
