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
use OCA\Mail\Service\ContextChat\JobsService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\ContextChat\ContentItem;
use OCP\ContextChat\IContentManager;

class SubmitContentJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private JobsService $jobsService,
		private AccountService $accountService,
		private MailManager $mailManager,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $clientFactory,
		private ContextChatProvider $contextChatProvider,
		private IContentManager $contentManager,
	) {
		parent::__construct($time);

		$this->setAllowParallelRuns(false);
		$this->setInterval(ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		if (!$this->contentManager->isContextChatAvailable()) {
			return;
		}

		$nextJob = $this->jobsService->findNext();
		$job = array_pop($nextJob);

		if ($job === null) {
			return;
		}

		// Remove job from the database while it is running
		// If new messages are received while the job is running,
		// allow the new job to be added and update it at the end if needed
		$this->jobsService->delete($job->getId());

		$account = $this->accountService->findById($job->getAccountId());
		$mailbox = $this->mailManager->getMailbox($job->getUserId(), $job->getMailboxId());
		$messageIds = $this->messageMapper->findAllIds($mailbox);
		$messageIds = array_filter($messageIds, fn (int $id): bool => $id >= $job->getNextMessageId());

		if (count($messageIds) === 0) {
			return;
		}

		$messages = $this->messageMapper->findByIds($job->getUserId(), $messageIds, 'asc');
		// Ensure messages are sorted by ID
		usort($messages, static fn (Message $a, Message $b): int => $a->getId() <=> $b->getId());

		$startTime = time() - ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE;
		$nextMessage = reset($messages);
		$client = $this->clientFactory->getClient($account);
		$items = [];

		while (($nextMessage !== false) && (count($items) < ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS)) {
			// Skip older messages
			if ($nextMessage->getSentAt() < $startTime) {
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
				[$job->getUserId()],
			);

			$nextMessage = next($messages);
		}

		if (count($items) > 0) {
			$this->contentManager->submitContent($this->contextChatProvider->getAppId(), $items);
		}

		if ($nextMessage !== false) {
			$this->jobsService->updateOrCreate($job->getUserId(), $job->getAccountId(), $job->getMailboxId(), $nextMessage->getId());
		}
	}
}
