<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\Actions;
use OCA\Mail\Db\ActionsMapper;
use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;

class QuickActionsService {

	public const AVAILABLE_ACTION_STEPS = [
		'markAsSpam',
		'applyTag',
		'snooze',
		'moveThread',
		'deleteThread',
		'markAsRead',
		'markAsUnread',
		'markAsImportant',
		'markAsFavorite'
	];

	public function __construct(
		private ActionsMapper $actionsMapper,
		private ActionStepMapper $actionStepMapper,
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

	public function create(string $name, int $accountId): Actions {
		$action = new Actions();
		$action->setName($name);
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

	/**
	 * @param string $userId
	 * @return ActionStep[]
	 */
	public function findAllActionSteps(int $actionId, string $userId): array {
		return $this->actionStepMapper->findAllStepsForOneAction($actionId, $userId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findActionStep(int $actionId, string $userId): ActionStep {
		return $this->actionStepMapper->find($actionId, $userId);
	}

	/**
	 * @param string $name
	 * @param int $order
	 * @param int $actionId
	 * @param string $parameter If the steps needs a parameter
	 * @return ActionStep
	 * @throws ServiceException
	 */
	public function createActionStep(string $name, int $order, int $actionId, ?int $tagId = null, ?int $mailboxId = null): ActionStep {
		$this->validateActionStep($name, $order, $actionId, $tagId, $mailboxId);
		$action = new ActionStep();
		$action->setName($name);
		$action->setOrder($order);
		$action->setActionId($actionId);
		$action->setTagId($tagId);
		$action->setMailboxId($mailboxId);
		return $this->actionStepMapper->insert($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function updateActionStep(ActionStep $action, string $name, int $order, ?int $tagId, ?int $mailboxId): ActionStep {
		$action->setName($name);
		$action->setOrder($order);
		if ($tagId !== null) {
			$action->setTagId($tagId);
		}
		if ($mailboxId !== null) {
			$action->setMailboxId($mailboxId);
		}
		return $this->actionStepMapper->update($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function deleteActionStep(int $actionId, string $userId): void {
		$action = $this->actionStepMapper->find($actionId, $userId);
		$this->actionStepMapper->delete($action);
	}

	/**
	 * @throws ServiceException
	 */
	private function validateActionStep(string $name, int $order, int $actionId, ?int $tagId, ?int $mailboxId): void {
		if (!in_array($name, self::AVAILABLE_ACTION_STEPS, true)) {
			throw new ServiceException('Invalid action step');
		}
		try {
			$highestOrderForAction = $this->actionStepMapper->findHighestOrderStep($actionId);

			if ($highestOrderForAction === null && $order > 1) {
				throw new ServiceException('Invalid action step order');
			}

			if ($highestOrderForAction && $highestOrderForAction->getName() === 'deleteThread') {
				throw new ServiceException('Cant perform actions after deleteThread');
			}

			if ($highestOrderForAction !== null && $order !== $highestOrderForAction->getOrder() + 1) {
				throw new ServiceException('Invalid action step order');
			}
		} catch (DoesNotExistException $th) {
			if ($order > 1) {
				throw new ServiceException('Invalid action step order');
			}
		}

		if ($name === 'applyTag' && $tagId === null) {
			throw new ServiceException('TagId is required for applyTag action step');
		}

		if ($name === 'moveThread' && $mailboxId === null) {
			throw new ServiceException('MailboxId is required for moveThread action step');
		}

	}
}
