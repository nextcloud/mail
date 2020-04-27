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

namespace OCA\Mail\Service\Classification;

use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class OftenContactedSenderImportanceEstimator implements IImportanceEstimator {
	use PercentageToRange;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IDBConnection */
	private $db;

	public function __construct(MailboxMapper $mailboxMapper,
								IDBConnection $db) {
		$this->mailboxMapper = $mailboxMapper;
		$this->db = $db;
	}

	public function estimateImportance(Account $account, Mailbox $mailbox, Message $message): ?int {
		$sender = $message->getTo()->first();
		if ($sender === null) {
			// Nothing to estimate
			return null;
		}

		try {
			$mb = $this->mailboxMapper->findSpecial($account, 'sent');
		} catch (DoesNotExistException $e) {
			// No data yet
			return null;
		}

		$totalSent = $this->getMessagesSentTotal($mb);
		if ($totalSent === 0) {
			// No data yet
			return null;
		}
		return $this->mapToRange(
			$this->getMessagesSentTo($mb, $sender->getEmail()) / $totalSent,
			IImportanceEstimator::RANGE_MIN,
			IImportanceEstimator::RANGE_MAX
		);
	}

	private function getMessagesSentTotal(Mailbox $mb): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id'))
			->join('r', 'mail_mailboxes', 'mb', $qb->expr()->eq('mb.id', 'm.mailbox_id'))
			->where($qb->expr()->eq('r.id', $qb->createNamedParameter(Address::TYPE_FROM), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('mb.id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	private function getMessagesSentTo(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->join('r', 'mail_mailboxes', 'mb', $qb->expr()->eq('mb.id', $qb->expr()->castColumn('m.mailbox_id', IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.id', $qb->createNamedParameter(Address::TYPE_FROM), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)))
			->andWhere($qb->expr()->eq('mb.id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}
}
