<?php

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

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class AliasMapper extends Mapper {

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
	public function find($aliasId, $currentUserId) {
		$sql = 'select *PREFIX*mail_aliases.* from *PREFIX*mail_aliases join *PREFIX*mail_accounts on *PREFIX*mail_aliases.account_id = *PREFIX*mail_accounts.id where *PREFIX*mail_accounts.user_id = ? and *PREFIX*mail_aliases.id=?';
		return $this->findEntity($sql, [$currentUserId, $aliasId]);
	}

	/**
	 * @param int $accountId
	 * @param string $currentUserId
	 *
	 * @return Alias[]
	 */
	public function findAll($accountId, $currentUserId) {
		$sql = 'select *PREFIX*mail_aliases.* from *PREFIX*mail_aliases join *PREFIX*mail_accounts on *PREFIX*mail_aliases.account_id = *PREFIX*mail_accounts.id where *PREFIX*mail_accounts.user_id = ? AND *PREFIX*mail_aliases.account_id=?';
		$params = [
			$currentUserId,
			$accountId
		];
		return $this->findEntities($sql, $params);
	}

	/**
	 * @param int $accountId the account whose aliases will be deleted
	 * @param string $currentUserId the user that is currently logged in
	 */
	public function deleteAll($accountId, $currentUserId) {
		$sql = 'DELETE FROM *PREFIX*mail_aliases USING *PREFIX*mail_accounts
					WHERE *PREFIX*mail_aliases.account_id = *PREFIX*mail_accounts.id
						AND *PREFIX*mail_accounts.user_id = ?
						AND *PREFIX*mail_aliases.account_id= ?';
		$params = [
			$currentUserId,
			$accountId
		];

		$stmt = $this->execute($sql, $params);
		$stmt->closeCursor();
	}
}
