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
use OCP\TaskProcessing\TaskTypes\TextToTextSummary;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event|NewMessagesSummarizeListener>
 */
class TaskProcessingListener implements IEventListener {

	public function __construct(
		protected LoggerInterface $logger,
		protected MessageMapper $messageStore,
	) { }

	public function handle(Event $event): void {

		if (!($event instanceof TaskSuccessfulEvent)) {
			return;
		}

		$task = $event->getTask();

		if ($task->getAppId() !== Application::APP_ID) {
			return;
		}

		if ($task->getTaskTypeId() !== TextToTextSummary::ID) {
			return;
		}

		list($type, $id) = explode(':', $task->getCustomId());
		$userId = $task->getUserId();
		$summary = $task->getOutput()['output'];

		match ($type) {
			'message' => $this->handleMessageSummary($userId, (int)$id, $summary),
		};

	}

	protected function handleMessageSummary(string $userId, int $id, string $summary) {
		$messages = $this->messageStore->findByIds($userId, [$id], '');

		if (count($messages) !== 1) {
			return;
		}

		$message = $messages[0];
		$message->setSummary($summary);
		$this->messageStore->update($message);
	}
}
