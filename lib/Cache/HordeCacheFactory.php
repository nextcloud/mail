<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Cache;

use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;

class HordeCacheFactory {
	public function __construct(
		private MailboxMapper $mailboxMapper,
		private MessageMapper $messageMapper,
		private HordeSyncTokenParser $syncTokenParser,
	) {
	}

	public function newCache(Account $account): Cache {
		return new Cache(
			$this->messageMapper,
			$this->mailboxMapper,
			$this->syncTokenParser,
			$account,
		);
	}
}
