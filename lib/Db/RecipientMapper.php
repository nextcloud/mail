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
 * @template-extends QBMapper<Recipient>
 */
class RecipientMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_recipients');
	}

	/**
	 * @returns Recipient[]
	 */
	public function findByLocalMessageId(int $localMessageId): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($query);
	}

	/**
	 *  @return Recipient[]
	 */
	public function findByLocalMessageIds(array $localMessageIds): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('local_message_id', $qb->createNamedParameter($localMessageIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);

		return $this->findEntities($query);
	}

	public function deleteForLocalMailbox(int $localMessageId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT))
			);
		$qb->execute();
	}

	/**
	 * @param int $localMessageId
	 * @param Recipient[] $recipients
	 * @param int $type
	 * @psalm-param Recipient::TYPE_* $type
	 */
	public function saveRecipients(int $localMessageId, array $recipients, int $type): void {
		if (empty($recipients)) {
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->getTableName());
		$qb->setValue('local_message_id', $qb->createParameter('local_message_id'));
		$qb->setValue('type', $qb->createParameter('type'));
		$qb->setValue('label', $qb->createParameter('label'));
		$qb->setValue('email', $qb->createParameter('email'));

		foreach ($recipients as $recipient) {
			$qb->setParameter('local_message_id', $localMessageId, IQueryBuilder::PARAM_INT);
			$qb->setParameter('type', $type, IQueryBuilder::PARAM_INT);
			$qb->setParameter('label', $recipient->getLabel() ?? $recipient->getEmail(), IQueryBuilder::PARAM_STR);
			$qb->setParameter('email', $recipient->getEmail(), IQueryBuilder::PARAM_STR);
			$qb->execute();
		}
	}
}
