<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<MessageSnooze>
 */
class MessageSnoozeMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private ITimeFactory $time,
	) {
		parent::__construct($db, 'mail_messages_snoozed', MessageSnooze::class);
	}

	/**
	 * Returns srcMailboxId (before snooze) for message
	 * Return null if no entry for message or srcMailboxId is null
	 *
	 * @param string $messageId
	 *
	 * @return int|null
	 */
	public function getSrcMailboxId(int $mailboxId, int $uid): ?int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('src_mailbox_id')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq(
					'mailbox_id',
					$qb->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT
				),
				$qb->expr()->eq(
					'uid',
					$qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT
				),
				$qb->expr()->isNotNull(
					'src_mailbox_id'
				),
			);

		$result = $select->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}
		return (int)$row['src_mailbox_id'];
	}

	/**
	 * Deletes DB Entry for woken message
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
				),
			);

		$delete->executeStatement();
	}

	/**
	 * Delete all orphaned entries that should have been unsnoozed a month ago.
	 * We assume that these messages no longer exist in the snoozed mailbox.
	 */
	public function deleteOrphans(): void {
		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete($this->getTableName())
			->where($deleteQb->expr()->lt(
				'snoozed_until',
				$deleteQb->createNamedParameter(($this->time->getTime() - (30 * 24 * 3600)), IQueryBuilder::PARAM_INT),
				IQueryBuilder::PARAM_INT,
			));
		$deleteQb->executeStatement();
	}
}
