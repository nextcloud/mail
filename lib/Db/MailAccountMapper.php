<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Db;

use Generator;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends QBMapper<MailAccount>
 */
class MailAccountMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_accounts');
	}

	/** Finds an Mail Account by id
	 *
	 * @param string $userId
	 * @param int $accountId
	 *
	 * @return MailAccount
	 *
	 * @throws DoesNotExistException
	 */
	public function find(string $userId, int $accountId): MailAccount {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($accountId)));

		return $this->findEntity($query);
	}

	/**
	 * Finds an mail account by id
	 *
	 * @return MailAccount
	 * @throws DoesNotExistException
	 */
	public function findById(int $id): MailAccount {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($query);
	}

	/**
	 * Finds all Mail Accounts by user id existing for this user
	 *
	 * @param string $userId the id of the user that we want to find
	 *
	 * @return MailAccount[]
	 */
	public function findByUserId(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		return $this->findEntities($query);
	}

	/**
	 * Finds a mail account(s) by user id and mail address
	 *
	 * @since 4.0.0
	 *
	 * @param string $userId			system user id
	 * @param string $address			mail address (e.g. test@example.com)
	 *
	 * @return MailAccount[]
	 *
	 * @throws DoesNotExistException
	 */
	public function findByUserIdAndAddress(string $userId, string $address): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('email', $qb->createNamedParameter($address)));

		return $this->findEntities($query);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function findProvisionedAccount(IUser $user): MailAccount {
		$qb = $this->db->getQueryBuilder();

		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($user->getUID())),
				$qb->expr()->isNotNull('provisioning_id')
			);

		return $this->findEntity($query);
	}

	/**
	 * Iterate over all accounts that follow system out-of-office settings
	 *
	 * @return Generator<MailAccount>
	 * @throws Exception
	 */
	public function findAllWhereOooFollowsSystem(): Generator {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->where($qb->expr()->eq(
				'ooo_follows_system',
				$qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL),
				IQueryBuilder::PARAM_BOOL))
			->from($this->getTableName());

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			yield $this->mapRowToEntity($row);
		}
		$result->closeCursor();
	}

	/**
	 * Saves an User Account into the database
	 *
	 * @param MailAccount $account
	 *
	 * @return MailAccount
	 */
	public function save(MailAccount $account): MailAccount {
		if ($account->getId() === null) {
			return $this->insert($account);
		}

		return $this->update($account);
	}

	public function deleteProvisionedAccounts(int $provisioningId): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where($qb->expr()->eq('provisioning_id', $qb->createNamedParameter($provisioningId, IQueryBuilder::PARAM_INT)));

		$delete->executeStatement();
	}

	public function deleteProvisionedAccountsByUid(string $uid): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->isNotNull('provisioning_id')
			);

		$delete->executeStatement();
	}

	public function deleteProvisionedOrphanAccounts(): void {
		$inner = $this->db->getQueryBuilder()
			->select('id')
			->from('mail_provisionings');

		$delete = $this->db->getQueryBuilder();
		$delete->delete($this->getTableName())
			->where(
				$delete->expr()->isNotNull('provisioning_id'),
				$delete->expr()->notIn(
					'provisioning_id',
					$delete->createFunction($inner->getSQL()),
					IQueryBuilder::PARAM_INT_ARRAY
				));

		$delete->executeStatement();
	}

	/**
	 * @return MailAccount[]
	 */
	public function getAllAccounts(): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName());

		return $this->findEntities($query);
	}

	/**
	 * @return int
	 */
	public function getTotal(): int {
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count())
			->from($this->getTableName());
		$result = $qb->executeQuery();

		$count = (int)$result->fetchColumn();
		$result->closeCursor();
		return $count;
	}

	public function getAllUserIdsWithAccounts(): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->selectDistinct('user_id')
			->from($this->getTableName());

		return $this->findEntities($query);
	}
}
