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

	//	public function __construct(IDb $db, $tableName, $entityClass=null){

	public function __construct(IDb $db){
		parent::__construct($db, 'mail_mailaccounts');
	}

	/** Finds an Mail Account by id
	 *
	 * @param int $userId
	 * @param int $mailAccountId
	 * @return MailAccount
	 */
	public function find($userId, $mailAccountId){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE ocuserid = ? and mailaccountid = ?';
		$params = array($userId, $mailAccountId);

		$row = $this->findOneQuery($sql, $params);
		return new MailAccount($row);
	}

	/**
	 * Finds all Mail Accounts by user id existing for this user
	 * @param string $userId the id of the user that we want to find
	 * @param $userId
	 * @return MailAccount[]
	 */
	public function findByUserId($userId){
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE ocuserid = ?';
		$params = array($userId);

		$result = $this->execute($sql, $params);
		$mailAccounts = array();
		while( $row = $result->fetchRow()) {
			$mailAccount = new MailAccount($row);
			$mailAccounts[] = $mailAccount;
		}

		return $mailAccounts;
	}

	/**
	 * Saves an User Account into the database
	 * @param MailAccount $mailAccount
	 * @internal param \OCA\Mail\Db\Account $User $userAccount the User Account to be saved
	 * @return MailAccount with the filled in mailaccountid
	 */
	public function save($mailAccount){
		$sql = 'INSERT INTO ' . $this->getTableName() . '(
			 `ocuserid`,
			 `mailaccountid`,
			 `mailaccountname`,
			 `email`,
			 `inboundhost`,
			 `inboundhostport`,
			 `inboundsslmode`,
			 `inbounduser`,
			 `inboundpassword`,
			 `inboundservice`,
			 `outboundhost`,
			 `outboundhostport`,
			 `outboundsslmode`,
			 `outbounduser`,
			 `outboundpassword`,
			 `outboundservice`
			 )' . 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

		$params = array(
			$mailAccount->getOcUserId(),
			$mailAccount->getMailAccountId(),
			$mailAccount->getMailAccountName(),
			$mailAccount->getEmail(),
			$mailAccount->getInboundHost(),
			$mailAccount->getInboundHostPort(),
			$mailAccount->getInboundSslMode(),
			$mailAccount->getInboundUser(),
			$mailAccount->getInboundPassword(),
			$mailAccount->getInboundService(),
			$mailAccount->getOutboundHost(),
			$mailAccount->getOutboundHostPort(),
			$mailAccount->getOutboundSslMode(),
			$mailAccount->getOutboundUser(),
			$mailAccount->getOutboundPassword(),
			$mailAccount->getOutboundService()
		);

		$this->execute($sql, $params);

		return $mailAccount;
	}

	/**
	 * Updates a Mail Account
	 * @param  MailAccount $mailAccount
	 */
	/*public function update($mailAccount){
		$sql = 'UPDATE ' . $this->getTableName() . 'SET
		 	`mailaccountname` = ?,
		 	`email` = ?,
		 	`inboundhost` = ?,
		 	`inboundhostport` = ?,
		 	`inboundsslmode` = ?,
		 	`inbounduser` = ?,
		 	`inboundpassword` = ?,
		 	`inboundservice` = ?,
		 	`outboundhost` = ?,
		 	`outboundhostport` = ?,
		 	`outboundsslmode` = ?,
		 	`outbounduser` = ?,
		 	`outboundpassword` = ?,
		 	`outboundservice` = ?
			WHERE `mailaccountid` = ?';

		$params = array(
			$mailAccount->getMailAccountName(),
			$mailAccount->getEmail(),
			$mailAccount->getInboundHost(),
			$mailAccount->getInboundHostPort(),
			$mailAccount->getInboundSslMode(),
			$mailAccount->getInboundUser(),
			$mailAccount->getInboundPassword(),
			$mailAccount->getInboundService(),
			$mailAccount->getOutboundHost(),
			$mailAccount->getOutboundHostPort(),
			$mailAccount->getOutboundSslMode(),
			$mailAccount->getOutboundUser(),
			$mailAccount->getOutboundPassword(),
			$mailAccount->getOutboundService(),
			$mailAccount->getMailAccountId()
		);

		$this->execute($sql, $params);
	}*/

	/**
	 * @param int $mailAccountId
	 */
	public function delete($accountId){
		$this->delete($this->getTableName(), $accountId);
	}

}
