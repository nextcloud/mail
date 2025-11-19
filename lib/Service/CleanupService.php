<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Db\MessageSnoozeMapper;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class CleanupService {
	private readonly ITimeFactory $timeFactory;

	public function __construct(
		private readonly MailAccountMapper $mailAccountMapper,
		private readonly \OCA\Mail\Db\AliasMapper $aliasMapper,
		private readonly \OCA\Mail\Db\MailboxMapper $mailboxMapper,
		private readonly \OCA\Mail\Db\MessageMapper $messageMapper,
		private readonly \OCA\Mail\Db\CollectedAddressMapper $collectedAddressMapper,
		private readonly \OCA\Mail\Db\TagMapper $tagMapper,
		private readonly MessageRetentionMapper $messageRetentionMapper,
		private readonly MessageSnoozeMapper $messageSnoozeMapper,
		ITimeFactory $timeFactory
	) {
		$this->timeFactory = $timeFactory;
	}

	public function cleanUp(LoggerInterface $logger): void {
		$task = (new PerformanceLogger(
			$this->timeFactory,
			$logger
		))->start('clean up');
		$this->mailAccountMapper->deleteProvisionedOrphanAccounts();
		$task->step('delete orphan provisioned accounts');
		$this->aliasMapper->deleteOrphans();
		$task->step('delete orphan aliases');
		$this->mailboxMapper->deleteOrphans();
		$task->step('delete orphan mailboxes');
		$this->messageMapper->deleteOrphans();
		$task->step('delete orphan messages');
		$this->collectedAddressMapper->deleteOrphans();
		$task->step('delete orphan collected addresses');
		$this->tagMapper->deleteOrphans();
		$task->step('delete orphan tags');
		$this->tagMapper->deleteDuplicates();
		$task->step('delete duplicate tags');
		$this->messageRetentionMapper->deleteOrphans();
		$task->step('delete expired messages');
		$this->messageSnoozeMapper->deleteOrphans();
		$task->step('delete orphan snoozes');
		$task->end();
	}
}
