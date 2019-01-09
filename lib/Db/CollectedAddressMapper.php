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

	public function exists($userId, $email) {
		$qb = $this->db->getQueryBuilder();
		$dbQuery = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->iLike('email', $qb->createNamedParameter($email)));

		return count($this->findEntities($dbQuery)) > 0;
	}

	public function getTotal() {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('COUNT(*)'))
			->from($this->getTableName());
		$result = $qb->execute();

		$count = (int)$result->fetchColumn(0);
		$result->closeCursor();
		return $count;
	}

	/**
	 * @param int|null $minId
	 */
	public function getChunk($minId = null) {
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

}
