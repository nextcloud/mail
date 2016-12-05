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

use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDb;

class CollectedAddressMapper extends Mapper {

	/**
	 * @param IDb $db
	 */
	public function __construct(IDb $db) {
		parent::__construct($db, 'mail_collected_addresses');
	}

	/**
	 * Find email addresses by query string
	 *
	 * @param string $userId
	 * @param string $query
	 * @return CollectedAddress[]
	 */
	public function findMatching($userId, $query) {
		$sql = 'SELECT * FROM *PREFIX*mail_collected_addresses WHERE `user_id` = ? AND (`email` ILIKE ? OR `display_name` ILIKE ?)';
		$params = [
			$userId,
			'%' . $query . '%',
			'%' . $query . '%',
		];
		return $this->findEntities($sql, $params);
	}

	public function exists($userId, $email) {
		$sql = 'SELECT * FROM *PREFIX*mail_collected_addresses WHERE `user_id` = ? AND `email` ILIKE ?';
		$params = [
			$userId,
			$email
		];
		return count($this->findEntities($sql, $params)) > 0;
	}

	public function getTotal() {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('COUNT(*)'))
			->from($this->getTableName());
		$result = $qb->execute();

		$count = (int) $result->fetchColumn(0);
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
		if (!is_null($minId)) {
			$query = $query->where($qb->expr()->gte('id',
					$qb->createNamedParameter($minId)));
		}

		$result = $qb->execute();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_map(function(array $data) {
			return CollectedAddress::fromRow($data);
		}, $rows);
	}

}
