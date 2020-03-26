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

class OftenImportantSenderClassifier extends AClassifier {
	use SafeRatio;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IDBConnection */
	private $db;

	public function __construct(MailboxMapper $mailboxMapper,
								IDBConnection $db) {
		$this->mailboxMapper = $mailboxMapper;
		$this->db = $db;
	}

	public function isImportant(Account $account, Mailbox $mailbox, Message $message): bool {
		$sender = $message->getTo()->first();
		if ($sender === null) {
			return false;
		}

		try {
			$mb = $this->mailboxMapper->findSpecial($account, 'inbox');
		} catch (DoesNotExistException $e) {
			return false;
		}

		return $this->greater(
			$this->getNrOfImportantMessages($mb, $sender->getEmail()),
			$this->getNumberOfMessages($mb, $sender->getEmail()),
			0.3
		);
	}

	private function getNrOfImportantMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->join('r', 'mail_mailboxes', 'mb', $qb->expr()->eq('mb.id', $qb->expr()->castColumn('m.mailbox_id', IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('mb.id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('m.flag_important', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}

	private function getNumberOfMessages(Mailbox $mb, string $email): int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select($qb->func()->count('*'))
			->from('mail_recipients', 'r')
			->join('r', 'mail_messages', 'm', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->join('r', 'mail_mailboxes', 'mb', $qb->expr()->eq('mb.id', $qb->expr()->castColumn('m.mailbox_id', IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM), IQueryBuilder::PARAM_INT))
			->andWhere($qb->expr()->eq('mb.id', $qb->createNamedParameter($mb->getId(), IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('r.email', $qb->createNamedParameter($email)));
		$result = $select->execute();
		$cnt = $result->fetchColumn();
		$result->closeCursor();
		return (int)$cnt;
	}
}
