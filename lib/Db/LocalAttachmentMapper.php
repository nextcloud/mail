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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class LocalAttachmentMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_attachments');
	}

	/**
	 * @throws DoesNotExistException
	 *
	 * @param int $userId
	 * @param int $id
	 *
	 * @return LocalAttachment
	 */
	public function find($userId, $id) {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($query);
	}
}
