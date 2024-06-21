<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_map;

/**
 * @template-extends QBMapper<CollectedAddress>
 */
class CollectedAddressMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_coll_addresses');
	}

	/**
	 * Find email addresses by query string
	 *
	 * @param string $userId
	 * @param string $query
	 *
	 * @return CollectedAddress[]
	 */
	public function findMatching($userId, $query) {
		$qb = $this->db->getQueryBuilder();
		$dbQuery = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->iLike('email', $qb->createNamedParameter("%$query%")),
				$qb->expr()->iLike('display_name', $qb->createNamedParameter("%$query%"))
			));

		return $this->findEntities($dbQuery);
	}

	public function insertIfNew(string $userId, string $email, ?string $label): bool {
		$qb = $this->db->getQueryBuilder();
		$dbQuery = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->iLike('email', $qb->createNamedParameter($email)));

		if (!empty($this->findEntities($dbQuery))) {
			return false;
		}

		$entity = new CollectedAddress();
		$entity->setUserId($userId);
		if ($label !== $email) {
			$entity->setDisplayName($label);
		}
		$entity->setEmail($email);
		$this->insert($entity);
		return true;
	}

	public function getTotal(): int {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count())
			->from($this->getTableName());
		$result = $qb->executeQuery();

		$count = (int)$result->fetchColumn();
		$result->closeCursor();
		return $count;
	}

	/**
	 * @param int|null $minId
	 *
	 * @return CollectedAddress[]
	 *
	 * @psalm-return array<CollectedAddress>
	 */
	public function getChunk($minId = null): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$query = $qb->select('*')
			->from($this->getTableName())
			->orderBy('id')
			->setMaxResults(100);
		if ($minId !== null) {
			$query = $query->where($qb->expr()->gte('id',
				$qb->createNamedParameter($minId)));
		}

		return $this->findEntities($query);
	}

	public function deleteOrphans(): void {
		$qb1 = $this->db->getQueryBuilder();
		$idsQuery = $qb1->selectDistinct('c.id')
			->from($this->getTableName(), 'c')
			->leftJoin('c', 'mail_accounts', 'a', $qb1->expr()->eq('c.user_id', 'a.user_id'))
			->where($qb1->expr()->isNull('a.id'));
		$result = $idsQuery->executeQuery();
		$ids = array_map(static function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createParameter('ids')));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$query->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->executeStatement();
		}
	}
}
