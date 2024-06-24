<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Classifier>
 */
class ClassifierMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_classifiers');
	}

	/**
	 * @param int $id
	 *
	 * @return Classifier
	 * @throws DoesNotExistException
	 */
	public function findLatest(int $id): Classifier {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('account_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->eq('active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL)
			)
			->orderBy('created_at', 'desc')
			->setMaxResults(1);

		return $this->findEntity($select);
	}

	public function findHistoric(int $threshold, int $limit) {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->lte('created_at', $qb->createNamedParameter($threshold, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
			)
			->orderBy('created_at', 'asc')
			->setMaxResults($limit);
		return $this->findEntities($select);
	}
}
