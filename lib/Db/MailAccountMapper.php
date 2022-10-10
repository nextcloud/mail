<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Lukas Reschke <lukas@owncloud.com>
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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
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

		$delete->execute();
	}

	public function deleteProvisionedAccountsByUid(string $uid): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->isNotNull('provisioning_id')
			);

		$delete->execute();
	}

	public function getAllAccounts(): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName());

		return $this->findEntities($query);
	}

	public function getAllUserIdsWithAccounts(): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->selectDistinct('user_id')
			->from($this->getTableName());

		return $this->findEntities($query);
	}
}
