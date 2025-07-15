<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob\ContextChat;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\ContextChat\ContextChatProvider;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\ContextChat\ContentItem;
use OCP\ContextChat\IContentManager;

class SubmitContentJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private AccountService $accountService,
		private MailManager $mailManager,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $clientFactory,
		private ContextChatProvider $contextChatProvider,
		private IContentManager $contentManager,
		private IJobList $jobList,
	) {
		parent::__construct($time);

		// Only run one instance of this job at a time
		$this->setAllowParallelRuns(false);
	}

	#[\Override]
	protected function run($argument): void {
		$account = $this->accountService->findById($argument['accountId']);
		$mailbox = $this->mailManager->getMailbox($argument['userId'], $argument['mailboxId']);
		$messageIds = $this->messageMapper->findAllIds($mailbox);
		$messageIds = array_filter($messageIds, fn (int $id): bool => $id >= $argument['nextMessageId']);

		if (count($messageIds) === 0) {
			// No more messages to process
			return;
		}

		$messages = $this->messageMapper->findByIds($argument['userId'], $messageIds, 'asc');
		// Ensure messages are sorted by ID
		usort($messages, static fn (Message $a, Message $b): int => $a->getId() <=> $b->getId());

		$nextMessage = reset($messages);
		$client = $this->clientFactory->getClient($account);
		$items = [];

		while (($nextMessage !== false) && (count($items) < Application::CONTEXT_CHAT_IMPORT_MAX_ITEMS)) {
			// Skip older messages
			if ($nextMessage->getSentAt() < $argument['startTime']) {
				$nextMessage = next($messages);
				continue;
			}

			$imapMessage = $this->mailManager->getImapMessage($client, $account, $mailbox, $nextMessage->getUid(), true);

			// Skip encrypted messages
			if ($imapMessage->isEncrypted()) {
				$nextMessage = next($messages);
				continue;
			}

			$fullMessage = $imapMessage->getFullMessage($imapMessage->getUid(), true);

			$items[] = new ContentItem(
				(string)$nextMessage->getId(),
				$this->contextChatProvider->getId(),
				$imapMessage->getSubject(),
				$fullMessage['body'] ?? '',
				'E-Mail',
				$imapMessage->getSentDate(),
				[$argument['userId']],
			);

			$nextMessage = next($messages);
		}

		if (count($items) > 0) {
			$this->contentManager->submitContent($this->contextChatProvider->getAppId(), $items);
		}

		// Schedule next job to process remaining messages
		if ($nextMessage !== false) {
			// Detect and replace pendings jobs for this mailbox to avoid duplicates
			$nextMessageIds = [$nextMessage->getId()];
			$jobs = $this->jobList->getJobsIterator(SubmitContentJob::class, null, 0);
			foreach ($jobs as $job) {
				$arg = $job->getArgument();
				if (
					($arg['userId'] === $argument['userId'])
					&& ($arg['accountId'] === $argument['accountId'])
					&& ($arg['mailboxId'] === $argument['mailboxId'])
				) {
					$nextMessageIds[] = $arg['nextMessageId'];
					$this->jobList->removeById($job->getId());
				}
			}

			$newArgument = $argument;
			$newArgument['nextMessageId'] = min($nextMessageIds);
			$this->jobList->scheduleAfter(SubmitContentJob::class, time() + Application::CONTEXT_CHAT_JOB_INTERVAL, $newArgument);
		}
	}
}
