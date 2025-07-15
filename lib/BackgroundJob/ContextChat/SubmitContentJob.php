<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob\ContextChat;

use OCA\Mail\ContextChat\ContextChatProvider;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\ContextChat\ContentItem;
use OCP\ContextChat\IContentManager;

class SubmitContentJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private MailManager $mailManager,
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
		$client = $this->clientFactory->getClient($argument['account']);
		$items = [];

		// Limit number of items to submit for current job
		while (count($items) < 100) {
			$message = array_pop($argument['messages']);
			if (!$message) {
				// No more messages to process
				break;
			}

			// Skip older messages
			if ($message->getSentAt() < $argument['startTime']) {
				continue;
			}

			$imapMessage = $this->mailManager->getImapMessage($client, $argument['account'], $argument['mailbox'], $message->getUid(), true);

			// Skip encrypted messages
			if ($imapMessage->isEncrypted()) {
				continue;
			}

			$fullMessage = $imapMessage->getFullMessage($imapMessage->getUid(), true);

			$items[] = new ContentItem(
				(string)$message->getId(),
				$this->contextChatProvider->getId(),
				$imapMessage->getSubject(),
				$fullMessage['body'] ?? '',
				'E-Mail',
				$imapMessage->getSentDate(),
				[$argument['userId']],
			);
		}

		if ($items) {
			$this->contentManager->submitContent($this->contextChatProvider->getAppId(), $items);
		}

		// Schedule next job to process remaining messages
		if ($argument['messages']) {
			$this->jobList->add(SubmitContentJob::class, $argument);
		}
	}
}
