<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\ContextChat;

use OCA\Mail\Db\ContextChat\Job;
use OCA\Mail\Db\ContextChat\JobMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class JobsService {
	public function __construct(
		private JobMapper $jobMapper,
	) {
	}

	/**
	 * @return Job[]
	 */
	public function findNext(): array {
		return $this->jobMapper->findNext();
	}

	/**
	 * Update job, or create it if it doesn't exist
	 *
	 * @param string $userId
	 * @param int $accountId
	 * @param int $mailboxId
	 * @param int $nextMessageId
	 * @return Job
	 */
	public function updateOrCreate(string $userId, int $mailboxId, int $accountId, int $nextMessageId): Job {
		try {
			$entity = $this->jobMapper->findByMailbox($userId, $accountId, $mailboxId);
		} catch (DoesNotExistException) {
			$entity = new Job();
			$entity->setUserId($userId);
			$entity->setAccountId($accountId);
			$entity->setMailboxId($mailboxId);
			$entity->setNextMessageId($nextMessageId);

			return $this->jobMapper->insert($entity);
		}

		if ($nextMessageId >= $entity->getNextMessageId()) {
			// Existing job already starts at an earlier message, so updating the database is not needed
			return $entity;
		}

		$entity->setNextMessageId($nextMessageId);
		return $this->jobMapper->update($entity);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function delete(int $jobId): Job {
		$entity = $this->jobMapper->findById($jobId);

		return $this->jobMapper->delete($entity);
	}
}
