<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<ActionStep>
 */
class ActionStepMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_action_step');
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $owner): ActionStep {
		$qb = $this->db->getQueryBuilder();
		$qb->select('step.*')
			->from($this->getTableName(), 'step')
			->join('step', 'mail_actions', 'actions', $qb->expr()->eq('step.action_id', 'actions.id'))
			->join('actions', 'mail_accounts', 'accounts', $qb->expr()->eq('actions.account_id', 'accounts.id'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('step.id', $qb->createNamedParameter($id)),
					$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($owner))
				)
			);
		return $this->findEntity($qb);
	}
	/**
	 * @param mixed $actionId
	 * @param string $owner Action's owner
	 * @return ActionStep[]
	 */
	public function findAllStepsForOneAction(int $actionId, string $owner) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('step.*')
			->from($this->getTableName(), 'step')
			->join('step', 'mail_actions', 'actions', $qb->expr()->eq('step.action_id', 'actions.id'))
			->join('actions', 'mail_accounts', 'accounts', $qb->expr()->eq('actions.account_id', 'accounts.id'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('step.action_id', $qb->createNamedParameter($actionId, IQueryBuilder::PARAM_INT)),
					$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($owner))
				)
			)
			->orderBy('order', 'ASC');

		return $this->findEntities($qb);
	}

	public function findHighestOrderStep(int $actionId): ?ActionStep {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('action_id', $qb->createNamedParameter($actionId, IQueryBuilder::PARAM_INT))
			)
			->orderBy('order', 'DESC')
			->setMaxResults(1);

		return $this->findEntity($qb);
	}
}
