<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob\ContextChat;

use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\ContextChatSettingsService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\ContextChat\IContentManager;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class ScheduleJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private TaskService $taskService,
		private AccountService $accountService,
		private MailManager $mailManager,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private ContextChatSettingsService $contextChatSettingsService,
		private IContentManager $contentManager,
	) {
		parent::__construct($time);

		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$accountId = $argument['accountId'];

		if (!$this->contentManager->isContextChatAvailable()) {
			return;
		}

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		if (!$this->contextChatSettingsService->isIndexingEnabled($account->getUserId())) {
			$this->logger->debug("indexing is turned off for account $accountId");
			return;
		}

		try {
			$mailboxes = $this->mailManager->getMailboxes($account);
		} catch (ServiceException $e) {
			$this->logger->debug("Could not find mailboxes for account <{$accountId}>");
			return;
		}

		foreach ($mailboxes as $mailbox) {
			try {
				$this->taskService->findByMailboxId($mailbox->getId());
				$this->taskService->updateOrCreate($mailbox->getId(), 0);
			} catch (DoesNotExistException|MultipleObjectsReturnedException|Exception $e) {
				$this->logger->warning("Could not schedule context chat indexing tasks for mailbox <{$mailbox->getId()}>");
			}
		}
	}
}
