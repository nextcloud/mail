<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\Mail\Address;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_column;
use function array_combine;
use function array_map;

class StatisticsDao {
	/** @var IDBConnection */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	private function emailCountResultToIndexedArray(array $rows): array {
		return array_combine(
			array_column($rows, 'email'),
			array_map(static function (string $val) {
				return (int)$val;
			}, array_column($rows, 'count'))
		);
	}

	public function getMessagesTotal(Mailbox ...$mb): int {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static function (Mailbox $mb) {
			return $mb->getId();
		}, $mb);
		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id'))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	public function getMessagesSentTo(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_TO), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)))
			->andWhere($qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	public function getMessagesSentToGrouped(array $mailboxes, array $emails): array {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static function (Mailbox $mb) {
			return $mb->getId();
		}, $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_TO), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->groupBy('r.email');
		$result = $select->execute();
		$rows = $result->fetchAll();
		$data = $this->emailCountResultToIndexedArray($rows);
		$result->closeCursor();
		return $data;
	}

	public function getNrOfImportantMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('m.flag_important', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	public function getNumberOfMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	/**
	 * @param Mailbox[] $mailboxes
	 * @param string[] $emails
	 *
	 * @return int[]
	 */
	public function getNumberOfMessagesGrouped(array $mailboxes, array $emails): array {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static function (Mailbox $mb) {
			return $mb->getId();
		}, $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->groupBy('r.email');
		$result = $select->execute();
		$rows = $result->fetchAll();
		$data = $this->emailCountResultToIndexedArray($rows);
		$result->closeCursor();
		return $data;
	}

	public function getNrOfReadMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('m.flag_seen', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	/**
	 * @param Mailbox[] $mailboxes
	 * @param string $flag
	 * @param string[] $emails
	 *
	 * @return int[]
	 */
	public function getNumberOfMessagesWithFlagGrouped(array $mailboxes, string $flag, array $emails): array {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static function (Mailbox $mb) {
			return $mb->getId();
		}, $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->eq("m.flag_$flag", $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->groupBy('r.email');
		$result = $select->execute();
		$rows = $result->fetchAll();
		$data = $this->emailCountResultToIndexedArray($rows);
		$result->closeCursor();
		return $data;
	}

	public function getNrOfRepliedMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('m.flag_answered', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}
}
