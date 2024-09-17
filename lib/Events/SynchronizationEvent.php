<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\MailAccount;
use OCP\EventDispatcher\Event;
use Psr\Log\LoggerInterface;

class SynchronizationEvent extends Event {

	/** @var LoggerInterface */
	private $logger;

	/** @var bool */
	private $rebuildThreads;

	public function __construct(private MailAccount $account,
		LoggerInterface $logger,
		bool $rebuildThreads) {
		parent::__construct();
		$this->logger = $logger;
		$this->rebuildThreads = $rebuildThreads;
	}

	public function getAccount(): MailAccount {
		return $this->account;
	}

	public function getLogger(): LoggerInterface {
		return $this->logger;
	}

	public function isRebuildThreads(): bool {
		return $this->rebuildThreads;
	}
}
