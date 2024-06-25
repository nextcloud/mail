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

	/**
	 * Find message entity ids of a thread than have been sent after the given message.
	 * Can be used to find out if a message has been replied to or followed up.
	 *
	 * @return array<array-key, array{id: int}>
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function findNewerMessageIdsInThread(int $accountId, Message $message): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('messages.id')
			->from($this->tableName, 'messages')
			->join('messages', 'mail_mailboxes', 'mailboxes', 'messages.mailbox_id = mailboxes.id')
			->where(
				// Not the message itself
				$qb->expr()->neq(
					'messages.message_id',
					$qb->createNamedParameter($message->getMessageId(), IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				// Are part of the same thread
				$qb->expr()->eq(
					'messages.thread_root_id',
					$qb->createNamedParameter($message->getThreadRootId(), IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
				// Are sent after the message
				$qb->expr()->gte(
					'messages.sent_at',
					$qb->createNamedParameter($message->getSentAt(), IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
				// Belong to the same account
				$qb->expr()->eq(
					'mailboxes.account_id',
					$qb->createNamedParameter($accountId, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
			);

		$result = $qb->executeQuery();
		$rows = array_map(static function (array $row) {
			return [
				'id' => (int)$row[0],
			];
		}, $result->fetchAll(\PDO::FETCH_NUM));
		$result->closeCursor();

		return $rows;
	}

}
