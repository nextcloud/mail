<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCP\EventDispatcher\Event;
use Psr\Log\LoggerInterface;

class SynchronizationEvent extends Event {
	public function __construct(
		private readonly \OCA\Mail\Account $account,
		private readonly \Psr\Log\LoggerInterface $logger,
		private readonly bool $rebuildThreads
	) {
		parent::__construct();
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getLogger(): LoggerInterface {
		return $this->logger;
	}

	public function isRebuildThreads(): bool {
		return $this->rebuildThreads;
	}
}
