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
	public function deleteByMailboxIdAndUid(int $mailboxId, int $uid): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq(
					'mailbox_id',
					$qb->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->eq(
					'uid',
					$qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				)
			);

		$delete->executeStatement();
	}

	/**
	 * Delete all orphaned extra entries that have no matching message anymore.
	 *
	 * @todo if this executes before the sync of the trash mailbox, there are false orphans. delete only *old* orphans?
	 */
	public function deleteOrphans(): void {
		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete($this->getTableName())
			->where(
				$deleteQb->expr()->eq(
					'mailbox_id',
					$deleteQb->createParameter('mailbox_id'),
					IQueryBuilder::PARAM_INT,
				),
				$deleteQb->expr()->eq(
					'uid',
					$deleteQb->createParameter('uid'),
					IQueryBuilder::PARAM_INT,
				),
			);

		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('mr.id')
			->from($this->getTableName(), 'mr')
			->leftJoin('mr', 'mail_messages', 'm', $selectQb->expr()->andX(
				$selectQb->expr()->eq(
					'm.mailbox_id',
					'mr.mailbox_id',
					IQueryBuilder::PARAM_INT,
				),
				$selectQb->expr()->eq(
					'm.uid',
					'mr.uid',
					IQueryBuilder::PARAM_INT,
				),
			))
			->where($selectQb->expr()->isNull('m.id'));
		$cursor = $selectQb->executeQuery();
		while ($row = $cursor->fetch()) {
			$deleteQb->setParameter('mailbox_id', $row['m.mailbox_id'], IQueryBuilder::PARAM_INT);
			$deleteQb->setParameter('uid', $row['m.uid'], IQueryBuilder::PARAM_INT);
			$deleteQb->executeStatement();
		}
		$cursor->closeCursor();
	}
}
