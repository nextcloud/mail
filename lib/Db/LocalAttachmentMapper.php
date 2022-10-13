<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

/**
 * @template-extends QBMapper<LocalAttachment>
 */
class LocalAttachmentMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_attachments');
	}

	/**
	 * @return LocalAttachment[]
	 */
	public function findByLocalMessageId(string $userId, int $localMessageId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere(
				$qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		return $this->findEntities($qb);
	}

	/**
	 * @return LocalAttachment[]
	 */
	public function findByLocalMessageIds(array $localMessageIds): array {
		if (empty($localMessageIds)) {
			return [];
		}
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('local_message_id', $qb->createNamedParameter($localMessageIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);
		return $this->findEntities($qb);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(string $userId, int $id): LocalAttachment {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));

		return $this->findEntity($query);
	}

	/**
	 * @throws Throwable
	 * @throws \OCP\DB\Exception
	 */
	public function deleteForLocalMessage(string $userId, int $localMessageId): void {
		$this->db->beginTransaction();
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->delete($this->getTableName())
				->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
				->andWhere($qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId), IQueryBuilder::PARAM_INT));
			$qb->execute();
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @throws Throwable
	 * @throws \OCP\DB\Exception
	 */
	public function saveLocalMessageAttachments(string $userId, int $localMessageId, array $attachmentIds): void {
		$this->db->beginTransaction();
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->update($this->getTableName())
				->set('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT))
				->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
				->andWhere(
					$qb->expr()->in('id', $qb->createNamedParameter($attachmentIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
				);
			$qb->execute();
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @return LocalAttachment[]
	 * @throws \OCP\DB\Exception
	 */
	public function findByIds(string $userId, array $attachmentIds): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere(
				$qb->expr()->in('id', $qb->createNamedParameter($attachmentIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);
		return $this->findEntities($qb);
	}
}
