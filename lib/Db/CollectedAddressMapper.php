<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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

	/**
	 * @param null|string $email
	 * @return bool
	 */
	public function exists(string $userId, ?string $email) {
		$qb = $this->db->getQueryBuilder();
		$dbQuery = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->iLike('email', $qb->createNamedParameter($email)));

		return count($this->findEntities($dbQuery)) > 0;
	}

	public function getTotal(): int {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count())
			->from($this->getTableName());
		$result = $qb->execute();

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
		$result = $idsQuery->execute();
		$ids = array_map(function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createParameter('ids')));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$query->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->execute();
		}
	}
}
