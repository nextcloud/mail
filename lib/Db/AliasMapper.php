<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_map;

/**
 * @template-extends QBMapper<Alias>
 */
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
	 * @throws DoesNotExistException
	 */
	public function find(int $aliasId, string $currentUserId): Alias {
		$qb = $this->db->getQueryBuilder();
		$qb->select('aliases.*', 'accounts.provisioning_id')
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
	 * @throws DoesNotExistException
	 */
	public function findByAlias(string $alias, string $currentUserId): Alias {
		$qb = $this->db->getQueryBuilder();
		$qb->select('aliases.*', 'accounts.provisioning_id')
			->from($this->getTableName(), 'aliases')
			->join('aliases', 'mail_accounts', 'accounts', $qb->expr()->eq('aliases.account_id', 'accounts.id'))
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($currentUserId)),
					$qb->expr()->eq('aliases.alias', $qb->createNamedParameter($alias))
				)
			);

		return $this->findEntity($qb);
	}

	/**
	 * @param int $accountId
	 * @param string $currentUserId
	 *
	 * @return list<Alias>
	 */
	public function findAll(int $accountId, string $currentUserId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('aliases.*', 'accounts.provisioning_id')
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
	 *
	 * @return void
	 */
	public function deleteAll($accountId): void {
		$qb = $this->db->getQueryBuilder();

		$query = $qb
			->delete($this->getTableName())
			->where($qb->expr()->eq('account_id', $qb->createNamedParameter($accountId)));

		$query->executeStatement();
	}

	/**
	 * Delete all provisioned aliases for the given uid
	 *
	 * Exception for Nextcloud 20: \Doctrine\DBAL\DBALException
	 * Exception for Nextcloud 21 and newer: \OCP\DB\Exception
	 *
	 * @TODO: Change throws to \OCP\DB\Exception once Mail does not support Nextcloud 20.
	 *
	 * @throws \Exception
	 */
	public function deleteProvisionedAliasesByUid(string $uid): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName(), 'aliases')
			->join('aliases', 'mail_accounts', 'accounts', 'accounts.id = aliases.account_id')
			->where(
				$qb->expr()->eq('accounts.user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->isNotNull('provisioning_id')
			);

		$qb->executeStatement();
	}

	public function deleteOrphans(): void {
		$qb1 = $this->db->getQueryBuilder();
		$idsQuery = $qb1->select('a.id')
			->from($this->getTableName(), 'a')
			->leftJoin('a', 'mail_accounts', 'ac', $qb1->expr()->eq('a.account_id', 'ac.id'))
			->where($qb1->expr()->isNull('ac.id'));
		$result = $idsQuery->executeQuery();
		$ids = array_map(static function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$qb2->delete($this->getTableName())
			->where(
				$qb2->expr()->in('id', $qb2->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY)
			);
		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb2->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$qb2->executeStatement();
		}
	}
}
