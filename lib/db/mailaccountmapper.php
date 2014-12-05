<?php
/**
 * ownCloud - Mail app
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class MailAccountMapper extends Mapper {

	public function __construct(IDb $db) {
		parent::__construct($db, 'mail_accounts');
	}

	/** Finds an Mail Account by id
	 *
	 * @param int $userId
	 * @param int $accountId
	 * @return MailAccount
	 */
	public function find($userId, $accountId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE user_id = ? and id = ?';
		$params = array($userId, $accountId);

		return $this->findEntity($sql, $params);
	}

	/**
	 * Finds all Mail Accounts by user id existing for this user
	 * @param string $userId the id of the user that we want to find
	 * @param $userId
	 * @return MailAccount[]
	 */
	public function findByUserId($userId) {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE user_id = ?';
		$params = array($userId);

		return $this->findEntities($sql, $params);
	}

	/**
	 * Saves an User Account into the database
	 * @param MailAccount $account
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
