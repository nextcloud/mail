<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\ContextChat;

use OCA\Mail\Db\ContextChat\Task;
use OCA\Mail\Db\ContextChat\TaskMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;

class TaskService {
	public function __construct(
		private TaskMapper $taskMapper,
	) {
	}

	/**
	 * @return Task
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function findNext(): Task {
		return $this->taskMapper->findNext();
	}

	/**
	 * Update job, or create it if it doesn't exist
	 *
	 * @param int $mailboxId
	 * @param int $lastMessageId
	 * @return Task
	 * @throws Exception|\OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	public function updateOrCreate(int $mailboxId, int $lastMessageId): Task {
		try {
			$entity = $this->taskMapper->findByMailbox($mailboxId);
		} catch (DoesNotExistException) {
			$entity = new Task();
			$entity->setMailboxId($mailboxId);
			$entity->setLastMessageId($lastMessageId);

			return $this->taskMapper->insert($entity);
		}

		if ($lastMessageId >= $entity->getLastMessageId()) {
			// Existing job already starts at an earlier message, so updating the database is not needed
			return $entity;
		}

		$entity->setLastMessageId($lastMessageId);
		return $this->taskMapper->update($entity);
	}

	/**
	 * @param int $jobId
	 * @return Task|null
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function delete(int $jobId): ?Task {
		try {
			$entity = $this->taskMapper->findById($jobId);
		} catch (DoesNotExistException) {
			return null;
		}
		return $this->taskMapper->delete($entity);
	}
}
