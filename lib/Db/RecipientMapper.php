<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Message>
 */
class RecipientMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_recipients');
	}

	/**
	 * @returns Recipient[]
	 * @throws \OCP\DB\Exception
	 */
	public function findRecipients(int $messageId, int $mailboxType = Recipient::TYPE_INBOX): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('mailbox_type', $qb->createNamedParameter($mailboxType, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($query);
	}

	public function deleteForLocalMailbox(int $messageId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('mailbox_type', $qb->createNamedParameter(Recipient::TYPE_OUTBOX, IQueryBuilder::PARAM_INT))
			);

		$result = $qb->execute();
		$result->closeCursor();
	}

	public function createForLocalMailbox(int $messageId, string $label, string $email): Recipient {
		$recipient = new Recipient();
		$recipient->setType(Recipient::TYPE_TO);
		$recipient->setMessageId($messageId);
		$recipient->setMailboxType(Recipient::TYPE_OUTBOX);
		$recipient->setLabel($label);
		$recipient->setEmail($email);
		$this->insert($recipient);
		return $recipient;
	}
}
