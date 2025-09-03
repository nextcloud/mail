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
 * @template-extends QBMapper<Actions>
 */
class ActionsMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_actions');
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $owner): Actions {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		return $this->findEntity($qb);
	}
	/**
	 * @param string $owner Action's owner
	 * @return Actions[]
	 */
	public function findAll(string $owner) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}


}
