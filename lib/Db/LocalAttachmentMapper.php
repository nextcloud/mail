<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function OCA\Mail\array_flat_map;

/**
 * @template-extends QBMapper<LocalAttachment>
 */
class LocalAttachmentMapper extends QBMapper {
	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @throws DoesNotExistException|Exception|MultipleObjectsReturnedException
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
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db, ITimeFactory $timeFactory) {
		parent::__construct($db, 'mail_attachments');
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @return LocalAttachment[]
	 * @throws Exception
	 */
	public function findForLocalMailbox(int $localMessageId, string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('mail_lcl_mbx_attchmts')
			->where($qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId), IQueryBuilder::PARAM_INT));
		$result = $qb->execute();

		$attachmentIds = array_map(function ($row) {
			return $row['attachment_id'];
		}, $result->fetchAll());

		$result->closeCursor();

		if (empty($attachmentIds)) {
			return [];
		}

		return array_flat_map(function ($attachmentId) use ($userId) {
			return $this->findRows($userId, $attachmentId);
		}, $attachmentIds);
	}

	/**
	 * @return LocalAttachment[]
	 * @throws Exception
	 */
	public function findRows(string $userId, int $id): array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		return $this->findEntities($query);
	}

	/**
	 * @throws Exception
	 */
	public function deleteForLocalMailbox(int $localMessageId, string $userId): void {
		$attachments = $this->findForLocalMailbox($localMessageId, $userId);

		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->in('id', $attachments, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY);
		$result = $qb->execute();
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$qb2->delete('mail_lcl_mbx_attchmts')
			->where($qb2->expr()->eq('local_message_id', $qb2->createNamedParameter($localMessageId), IQueryBuilder::PARAM_INT));
		$result = $qb2->execute();
		$result->closeCursor();
	}

	/**
	 * @throws Exception
	 */
	public function createLocalMailboxAttachment(int $localMessageId, string $userId, string $fileName, string $mimetype): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->getTableName())
			->setValue('user_id', $qb->createNamedParameter($userId))
			->setValue('created_at', $qb->createNamedParameter($this->timeFactory->getTime()))
			->setValue('file_name', $qb->createNamedParameter($fileName))
			->setValue('mime_type', $qb->createNamedParameter($mimetype));
		$result = $qb->execute();
		$attachmentId = $qb->getLastInsertId();
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$qb2->insert('mail_lcl_mbx_attchmts')
			->setValue('local_message_id', $qb2->createNamedParameter($localMessageId))
			->setValue('attachment_id', $qb2->createNamedParameter($attachmentId));
		$result = $qb2->execute();
		$result->closeCursor();
	}

	/**
	 * @throws Exception
	 */
	public function linkAttachmentToMessage(int $messageId, int $attachmentId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_lcl_mbx_attchmts')
			->setValue('message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT))
			->setValue('attachment_id', $qb->createNamedParameter($attachmentId, IQueryBuilder::PARAM_INT));
		$result = $qb->execute();
		$result->closeCursor();
	}
}
