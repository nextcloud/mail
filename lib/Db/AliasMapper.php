<?php

declare(strict_types=1);

/**
 * Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @copyright Tahaa Karim 2016
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_map;

class AliasMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_aliases');
	}

	/**
	 * @param int $aliasId
	 * @param string $currentUserId
	 *
	 * @return Alias
	 */
	public function find(int $aliasId, string $currentUserId): Alias {
		$qb = $this->db->getQueryBuilder();
		$qb->select('aliases.*')
			->from($this->getTableName(), 'aliases')
			->join('aliases', 'mail_accounts', 'accounts', $qb->expr()->eq('aliases.account_id', 'accounts.id'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($currentUserId)),
					$qb->expr()->eq('aliases.id', $qb->createNamedParameter($aliasId))
				)
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param int $accountId
	 * @param string $currentUserId
	 *
	 * @return Alias[]
	 */
	public function findAll(int $accountId, string $currentUserId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('aliases.*')
			->from($this->getTableName(), 'aliases')
			->join('aliases', 'mail_accounts', 'accounts', $qb->expr()->eq('aliases.account_id', 'accounts.id'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($currentUserId)),
					$qb->expr()->eq('aliases.account_id', $qb->createNamedParameter($accountId))
				)
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param int $accountId the account whose aliases will be deleted
	 * @param string $currentUserId the user that is currently logged in
	 */
	public function deleteAll($accountId) {
		$qb = $this->db->getQueryBuilder();

		$query = $qb
			->delete($this->getTableName())
			->where($qb->expr()->eq('account_id', $qb->createNamedParameter($accountId)));

		$query->execute();
	}

	public function deleteOrphans(): void {
		$qb1 = $this->db->getQueryBuilder();
		$idsQuery = $qb1->select('a.id')
			->from($this->getTableName(), 'a')
			->leftJoin('a', 'mail_accounts', 'ac', $qb1->expr()->eq('a.account_id', 'ac.id'))
			->where($qb1->expr()->isNull('ac.id'));
		$result = $idsQuery->execute();
		$ids = array_map(function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY));
		$query->execute();
	}
}
