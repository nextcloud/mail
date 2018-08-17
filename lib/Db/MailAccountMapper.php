<?php

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

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

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
	 */
	public function find($userId, $accountId) {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($accountId)));

		return $this->findEntity($qb);
	}

	/**
	 * Finds all Mail Accounts by user id existing for this user
	 *
	 * @param string $userId the id of the user that we want to find
	 * @param $userId
	 *
	 * @return MailAccount[]
	 */
	public function findByUserId($userId) {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		return $this->findEntities($query);
	}

	/**
	 * Saves an User Account into the database
	 *
	 * @param MailAccount $account
	 *
	 * @return MailAccount
	 */
	public function save(MailAccount $account) {
		if (is_null($account->getId())) {
			return $this->insert($account);
		} else {
			$this->update($account);
			return $account;
		}
	}

}
