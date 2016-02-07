<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
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
		$sql = 'SELECT * FROM *PREFIX*mail_collected_addresses WHERE user_id = ? AND WHERE email ILIKE \'*?*\'';
		$params = [
			$userId,
			$query
		];
		return $this->findEntities($sql, $params);
	}

}
