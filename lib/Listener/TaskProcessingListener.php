<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Db\MessageMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\TaskProcessing\Events\TaskSuccessfulEvent;
use OCP\TaskProcessing\TaskTypes\TextToText;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class TaskProcessingListener implements IEventListener {

	public function __construct(
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {

		if (!($event instanceof TaskSuccessfulEvent)) {
			return;
		}

		$task = $event->getTask();

		if ($task->getAppId() !== Application::APP_ID) {
			return;
		}

		if ($task->getTaskTypeId() !== TextToText::ID) {
			return;
		}

		if ($task->getCustomId() && strpos($task->getCustomId(), ':') !== false) {
			[$type, $id] = explode(':', $task->getCustomId());
		} else {
			$this->logger->info('Error handling task processing event custom id missing', ['taskCustomId' => $task->getCustomId()]);
			return;
		}
		if ($type === null || $id === null) {
			$this->logger->info('Error handling task processing event custom id is invalid', ['taskCustomId' => $task->getCustomId()]);
			return;
		}
		if ($task->getUserId() !== null) {
			$userId = $task->getUserId();
		} else {
			$this->logger->info('Error handling task processing event user id missing');
			return;
		}
		if ($task->getOutput() !== null) {
			$output = $task->getOutput();
			if (isset($output['output']) && is_string($output['output'])) {
				$summary = $output['output'];
			} else {
				$this->logger->info('Error handling task processing event output is invalid', ['taskOutput' => $output]);
				return;
			}
		} else {
			$this->logger->info('Error handling task processing event output missing');
			return;
		}

		if ($type === 'message') {
			$this->handleMessageSummary($userId, (int)$id, $summary);
		}

	}

	private function handleMessageSummary(string $userId, int $id, string $summary): void {
		$messages = $this->messageMapper->findByIds($userId, [$id], '');

		if (count($messages) !== 1) {
			return;
		}

		$message = $messages[0];
		$message->setSummary(substr($summary, 0, 1024));
		$this->messageMapper->update($message);
	}
}
