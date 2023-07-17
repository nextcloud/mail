<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<MessageRetention>
 */
class MessageRetentionMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_messages_retention', MessageRetention::class);
	}

	/**
	 * @param string[] $messageIds
	 *
	 * @return void
	 */
	public function deleteByMessageIds(array $messageIds): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where($qb->expr()->in(
				'message_id',
				$qb->createParameter('message_ids'),
				IQueryBuilder::PARAM_STR_ARRAY,
			));

		foreach (array_chunk($messageIds, 500) as $messageIdChunk) {
			$delete->setParameter(
				'message_ids',
				$messageIdChunk,
				IQueryBuilder::PARAM_STR_ARRAY,
			);
			$delete->executeStatement();
		}
	}

	/**
	 * Delete all orphaned extra entries that have no matching message anymore.
	 */
	public function deleteOrphans(): void {
		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete($this->getTableName())
			->where('id', $deleteQb->expr()->in(
				'id',
				$deleteQb->createParameter('ids'),
				IQueryBuilder::PARAM_INT_ARRAY,
			));

		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('mr.id')
			->from($this->getTableName(), 'mr')
			->leftJoin('mr', 'mail_messages', 'm', $selectQb->expr()->eq(
				'm.message_id',
				'mr.message_id',
				IQueryBuilder::PARAM_STR,
			))
			->where($selectQb->expr()->isNull('m.id'));
		$cursor = $selectQb->executeQuery();
		$ids = [];
		while ($row = $cursor->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$cursor->closeCursor();

		foreach (array_chunk($ids, 500) as $idChunk) {
			$deleteQb->setParameter('ids', $idChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteQb->executeStatement();
		}
	}
}
