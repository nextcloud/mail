<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;

class ActionStepService {

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
		private ActionStepMapper $actionStepMapper,
	) {
	}

	/**
	 * @param string $userId
	 * @return ActionStep[]
	 */
	public function findAll(int $actionId): array {
		return $this->actionStepMapper->findAllStepsForOneAction($actionId);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $actionId, string $userId): ActionStep {
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
	public function create(string $name, int $order, int $actionId, string $parameter = ''): ActionStep {
		$this->validateActionStep($name, $order, $actionId);
		$action = new ActionStep();
		$action->setName($name);
		$action->setOrder($order);
		$action->setActionId($actionId);
		$action->setParameter($parameter);
		return $this->actionStepMapper->insert($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function update(ActionStep $action, string $name, string $parameter): ActionStep {
		$action->setName($name);
		$action->setParameter($parameter);
		return $this->actionStepMapper->update($action);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function delete(int $actionId, string $userId): void {
		$action = $this->actionStepMapper->find($actionId, $userId);
		$this->actionStepMapper->delete($action);
	}

	/**
	 * @throws ServiceException
	 */
	public function swapOrder(int $actionId, int $newOrder): void {
		$highestOrderForAction = $this->actionStepMapper->findHighestOrderStep($actionId);
		if ($highestOrderForAction && $highestOrderForAction->getOrder() <= $newOrder && $highestOrderForAction->getName() === 'deleteThread') {
			throw new ServiceException('Cant perform actions after deleteThread');
		}
		$this->actionStepMapper->swapOrder($actionId, $newOrder);
	}

	/**
	 * @throws ServiceException
	 */
	private function validateActionStep(string $name, int $order, int $actionId): void {
		if (!in_array($name, self::AVAILABLE_ACTION_STEPS)) {
			throw new ServiceException('Invalid action step');
		}
		try {
			$highestOrderForAction = $this->actionStepMapper->findHighestOrderStep($actionId);

			if ($highestOrderForAction && $highestOrderForAction->getName() === 'deleteThread') {
				throw new ServiceException('Cant perform actions after deleteThread');
			}

			if ($order !== $highestOrderForAction->getOrder() + 1) {
				throw new ServiceException('Invalid action step order');
			}
		} catch (DoesNotExistException $th) {
			if ($order > 1) {
				throw new ServiceException('Invalid action step order');
			}
		}

	}

}
