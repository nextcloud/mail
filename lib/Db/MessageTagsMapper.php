<?php

declare(strict_types=1);

/**
 * @copyright 2023 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author 2023 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<MessageTags>
 */
class MessageTagsMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_message_tags');
	}

	public function getMessagesByTag(int $id): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('tag_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),

			);
		return $this->findEntities($qb);
	}

}
