<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCA\Mail\Address;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_chunk;
use function array_column;
use function array_combine;
use function array_map;
use function array_reduce;

class StatisticsDao {
	/** @var IDBConnection */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	private function emailCountResultToIndexedArray(array $rows): array {
		return array_combine(
			array_column($rows, 'email'),
			array_map(static fn (string $val) => (int)$val, array_column($rows, 'count'))
		);
	}

	/**
	 * @see emailCountResultToIndexedArray
	 */
	private function groupEmailCountResults(array $results): array {
		$combined = [];
		foreach ($results as $index) {
			foreach ($index as $email => $count) {
				if (!isset($combined[$email])) {
					$combined[$email] = 0;
				}
				$combined[$email] += $count;
			}
		}
		return $combined;
	}

	public function getMessagesTotal(Mailbox ...$mb): int {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static fn (Mailbox $mb) => $mb->getId(), $mb);
		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id'))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createParameter('mailbox_ids'), IQueryBuilder::PARAM_INT_ARRAY));
		return array_reduce(array_chunk($mailboxIds, 1000), static function ($carry, $mailboxIds) use ($select) {
			$select->setParameter('mailbox_ids', $mailboxIds, IQueryBuilder::PARAM_INT_ARRAY);
			$result = $select->executeQuery();
			$cnt = $result->fetchOne();
			$result->closeCursor();
			return $carry + (int)$cnt;
		}, 0);
	}

	public function getMessagesSentToGrouped(array $mailboxes, array $emails): array {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static fn (Mailbox $mb) => $mb->getId(), $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_TO), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createParameter('mailbox_ids'), IQueryBuilder::PARAM_INT_ARRAY))
			->groupBy('r.email');
		return $this->groupEmailCountResults(
			array_map(function (array $mailboxIds) use ($select) {
				$select->setParameter('mailbox_ids', $mailboxIds, IQueryBuilder::PARAM_INT_ARRAY);
				$result = $select->executeQuery();
				$rows = $result->fetchAll();
				$data = $this->emailCountResultToIndexedArray($rows);
				$result->closeCursor();
				return $data;
			}, array_chunk($mailboxIds, 1000))
		);
	}

	/**
	 * @param Mailbox[] $mailboxes
	 * @param string[] $emails
	 *
	 * @return int[]
	 */
	public function getNumberOfMessagesGrouped(array $mailboxes, array $emails): array {
		$qb = $this->db->getQueryBuilder();

		$mailboxIds = array_map(static fn (Mailbox $mb) => $mb->getId(), $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createParameter('mailbox_ids')))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->groupBy('r.email');
		return $this->groupEmailCountResults(
			array_map(function (array $mailboxIds) use ($select) {
				$select->setParameter('mailbox_ids', $mailboxIds, IQueryBuilder::PARAM_INT_ARRAY);
				$result = $select->executeQuery();
				$rows = $result->fetchAll();
				$data = $this->emailCountResultToIndexedArray($rows);
				$result->closeCursor();
				return $data;
			}, array_chunk($mailboxIds, 1000))
		);
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

		$mailboxIds = array_map(static fn (Mailbox $mb) => $mb->getId(), $mailboxes);
		$select = $qb->selectAlias('r.email', 'email')
			->selectAlias($qb->func()->count('*'), 'count')
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->in('m.mailbox_id', $qb->createParameter('mailbox_ids')))
			->andWhere($qb->expr()->eq("m.flag_$flag", $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->in('r.email', $qb->createNamedParameter($emails, IQueryBuilder::PARAM_STR_ARRAY), IQueryBuilder::PARAM_STR_ARRAY))
			->groupBy('r.email');
		return $this->groupEmailCountResults(
			array_map(function (array $mailboxIds) use ($select) {
				$select->setParameter('mailbox_ids', $mailboxIds, IQueryBuilder::PARAM_INT_ARRAY);
				$result = $select->executeQuery();
				$rows = $result->fetchAll();
				$data = $this->emailCountResultToIndexedArray($rows);
				$result->closeCursor();
				return $data;
			}, array_chunk($mailboxIds, 1000))
		);
	}
}
