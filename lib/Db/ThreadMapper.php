<?php

declare(strict_types=1);

/**
 * @copyright 2021 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2021 Daniel Kesselberg <mail@danielkesselberg.de>
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
