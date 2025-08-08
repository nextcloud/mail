<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob\ContextChat;

use OCA\Mail\ContextChat\ContextChatProvider;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeDecryptException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\ContextChat\TaskService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\ContextChat\ContentItem;
use OCP\ContextChat\IContentManager;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class SubmitContentJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private TaskService $taskService,
		private AccountService $accountService,
		private MailManager $mailManager,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $clientFactory,
		private ContextChatProvider $contextChatProvider,
		private IContentManager $contentManager,
		private LoggerInterface $logger,
		private MailboxMapper $mailboxMapper,
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

		try {
			$task = $this->taskService->findNext();
		} catch (Exception $e) {
			$this->logger->warning('Exception occurred when trying to fetch next task', ['exception' => $e]);
			return;
		} catch (DoesNotExistException $e) {
			// nothing to be done, let's defer to the next iteration of this job
			return;
		} catch (MultipleObjectsReturnedException $e) {
			$this->logger->warning('Multiple tasks found for context chat. This is unexpected.', ['exception' => $e]);
			return;
		}

		try {
			$mailbox = $this->mailboxMapper->findById($task->getMailboxId());
		} catch (ServiceException $e) {
			$this->logger->warning('Multiple mailboxes found for context chat task, but only one expected. ERROR!', ['exception' => $e]);
			return;
		} catch (DoesNotExistException $e) {
			// mailbox does not exist, lets wait for this task to be removed
			return;
		}

		$processMailsAfter = $this->time->getTime() - ContextChatProvider::CONTEXT_CHAT_MESSAGE_MAX_AGE;
		$messageIds = $this->messageMapper->findIdsAfter($mailbox, $task->getLastMessageId(), $processMailsAfter, ContextChatProvider::CONTEXT_CHAT_IMPORT_MAX_ITEMS);

		if (empty($messageIds)) {
			try {
				$this->taskService->delete($task->getId());
			} catch (MultipleObjectsReturnedException|Exception $e) {
				$this->logger->warning('Exception occurred when trying to delete task', ['exception' => $e]);
			}
			return;
		}

		try {
			$account = $this->accountService->findById($mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			// well, what do you know. Then let's just skip this and wait for the next iteration of this job. tasks should be cascade deleted anyway
			return;
		}

		$messages = $this->messageMapper->findByIds($account->getUserId(), $messageIds, 'asc', 'id');

		if (empty($messages)) {
			try {
				$this->taskService->delete($task->getId());
			} catch (MultipleObjectsReturnedException|Exception $e) {
				$this->logger->warning('Exception occurred when trying to delete task', ['exception' => $e]);
			}
			return;
		}


		$client = $this->clientFactory->getClient($account);
		$items = [];

		try {
			$startTime = $this->time->getTime();
			foreach ($messages as $message) {
				if ($this->time->getTime() - $startTime > ContextChatProvider::CONTEXT_CHAT_JOB_INTERVAL) {
					break;
				}
				try {
					$imapMessage = $this->mailManager->getImapMessage($client, $account, $mailbox, $message->getUid(), true);
				} catch (ServiceException $e) {
					// couldn't load message, let's skip it. Retrying would be too costly
					continue;
				} catch (SmimeDecryptException $e) {
					// encryption problem, skip this message
					continue;
				}

				// Skip encrypted messages
				if ($imapMessage->isEncrypted()) {
					continue;
				}

				$fullMessage = $imapMessage->getFullMessage($imapMessage->getUid(), true);

				$items[] = new ContentItem(
					$mailbox->getId() . ':' . $message->getId(),
					$this->contextChatProvider->getId(),
					$imapMessage->getSubject(),
					$fullMessage['body'] ?? '',
					'E-Mail',
					$imapMessage->getSentDate(),
					[$account->getUserId()],
				);
			}
		} finally {
			try {
				$client->close();
			} catch (\Horde_Imap_Client_Exception $e) {
				// pass
			}
		}

		if (count($items) > 0) {
			$this->contentManager->submitContent($this->contextChatProvider->getAppId(), $items);
		}

		try {
			$this->taskService->updateOrCreate($task->getMailboxId(), $message?->getId() ?? $messageIds[0]);
		} catch (MultipleObjectsReturnedException|Exception $e) {
			$this->logger->warning('Exception occurred when trying to update task', ['exception' => $e]);
		}
	}
}
