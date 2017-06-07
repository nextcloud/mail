<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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
use OCP\IDBConnection;

class LocalAttachmentMapper extends Mapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_attachments');
	}

	/**
	 * @param int $userId
	 * @param int $id
	 * @return LocalAttachment
	 */
	public function find($userId, $id) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE user_id = ? and id = ?';
		$params = [$userId, $id];

		return $this->findEntity($sql, $params);
	}

}
