<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Actions;
use OCA\Mail\Db\ActionsMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class QuickActionsService {

	public function __construct(
		private ActionsMapper $actionsMapper,
	) {
	}

	/**
	 * @param string $userId
	 * @return Actions[]
	 */
	public function findAll(string $userId): array {
		return $this->actionsMapper->findAll($userId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $actionId, string $userId): ?Actions {
		return $this->actionsMapper->find($actionId, $userId);
	}

	public function create(string $userId, string $name, int $accountId): Actions {
		$action = new Actions();
		$action->setName($name);
		$action->setOwner($userId);
		$action->setAccountId($accountId);
		return $this->actionsMapper->insert($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function update(Actions $action, string $name): Actions {
		$action->setName($name);
		return $this->actionsMapper->update($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function delete(int $actionId, string $userId): void {
		$action = $this->actionsMapper->find($actionId, $userId);
		$this->actionsMapper->delete($action);
	}
}
