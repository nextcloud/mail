<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Message>
 */
class ThreadMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_messages');
	}

	/**
	 * @return array<array-key, array{mailboxName: string, messageUid: int}>
	 */
	public function findMessageUidsAndMailboxNamesByAccountAndThreadRoot(MailAccount $mailAccount, string $threadRootId, bool $trash): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('messages.uid', 'mailboxes.name')
			->from($this->tableName, 'messages')
			->join('messages', 'mail_mailboxes', 'mailboxes', 'messages.mailbox_id = mailboxes.id')
			->where(
				$qb->expr()->eq('messages.thread_root_id', $qb->createNamedParameter($threadRootId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('mailboxes.account_id', $qb->createNamedParameter($mailAccount->getId(), IQueryBuilder::PARAM_INT))
			);

		$trashMailboxId = $mailAccount->getTrashMailboxId();
		if ($trashMailboxId !== null) {
			if ($trash) {
				$qb->andWhere($qb->expr()->eq('mailboxes.id', $qb->createNamedParameter($trashMailboxId, IQueryBuilder::PARAM_INT)));
			} else {
				$qb->andWhere($qb->expr()->neq('mailboxes.id', $qb->createNamedParameter($trashMailboxId, IQueryBuilder::PARAM_INT)));
			}
		}

		$result = $qb->executeQuery();
		$rows = array_map(static function (array $row) {
			return [
				'messageUid' => (int)$row[0],
				'mailboxName' => (string)$row[1]
			];
		}, $result->fetchAll(\PDO::FETCH_NUM));
		$result->closeCursor();

		return $rows;
	}

}
