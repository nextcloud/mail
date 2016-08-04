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
		$sql = 'SELECT * FROM *PREFIX*mail_collected_addresses WHERE `user_id` = ? AND `email` ILIKE ?';
		$params = [
			$userId,
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

}
