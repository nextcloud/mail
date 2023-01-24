<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna@nextcloud.com>
 *
 * @author 2022 Anna Larch <anna@nextcloud.com>
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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception as DBException;
use Throwable;
use function array_map;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_merge;

/**
 * @template-extends QBMapper<LocalMessage>
 */
class LocalMessageMapper extends QBMapper {
	/** @var LocalAttachmentMapper */
	private $attachmentMapper;

	/** @var RecipientMapper */
	private $recipientMapper;

	public function __construct(IDBConnection $db,
								LocalAttachmentMapper $attachmentMapper,
								RecipientMapper $recipientMapper) {
		parent::__construct($db, 'mail_local_messages');
		$this->recipientMapper = $recipientMapper;
		$this->attachmentMapper = $attachmentMapper;
	}

	/**
	 * @param string $userId
	 * @return LocalMessage[]
	 * @throws DBException
	 */
	public function getAllForUser(string $userId, int $type = LocalMessage::TYPE_OUTGOING): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('m.*')
			->from('mail_accounts', 'a')
			->join('a', $this->getTableName(), 'm', $qb->expr()->eq('m.account_id', 'a.id'))
			->where(
				$qb->expr()->eq('a.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR),
				$qb->expr()->eq('m.type', $qb->createNamedParameter($type, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$rows = $qb->execute();

		$results = [];
		$ids = [];
		while (($row = $rows->fetch()) !== false) {
			$results[] = $this->mapRowToEntity($row);
			$ids[] = $row['id'];
		}
		$rows->closeCursor();

		if (empty($ids)) {
			return [];
		}

		$attachments = $this->attachmentMapper->findByLocalMessageIds($ids);
		$recipients = $this->recipientMapper->findByLocalMessageIds($ids);

		$recipientMap = [];
		foreach ($recipients as $r) {
			$recipientMap[$r->getLocalMessageId()][] = $r;
		}
		$attachmentMap = [];
		foreach ($attachments as $a) {
			$attachmentMap[$a->getLocalMessageId()][] = $a;
		}

		return array_map(static function ($localMessage) use ($attachmentMap, $recipientMap) {
			$localMessage->setAttachments($attachmentMap[$localMessage->getId()] ?? []);
			$localMessage->setRecipients($recipientMap[$localMessage->getId()] ?? []);
			return $localMessage;
		}, $results);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function findById(int $id, string $userId): LocalMessage {
		$qb = $this->db->getQueryBuilder();
		$qb->select('m.*')
			->from('mail_accounts', 'a')
			->join('a', $this->getTableName(), 'm', $qb->expr()->eq('m.account_id', 'a.id'))
			->where(
				$qb->expr()->eq('a.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR),
				$qb->expr()->eq('m.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$entity = $this->findEntity($qb);
		$entity->setAttachments($this->attachmentMapper->findByLocalMessageId($userId, $id));
		$entity->setRecipients($this->recipientMapper->findByLocalMessageId($id));
		return $entity;
	}

	/**
	 * Find all messages that should be sent
	 *
	 * @param int $time upper bound send time stamp
	 *
	 * @return LocalMessage[]
	 */
	public function findDue(int $time, int $type = LocalMessage::TYPE_OUTGOING): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->isNotNull('send_at'),
				$qb->expr()->eq('type', $qb->createNamedParameter($type, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->lte('send_at', $qb->createNamedParameter($time, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->orX(
					$qb->expr()->isNull('failed'),
					$qb->expr()->eq('failed', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				)
			)
			->orderBy('send_at', 'asc');
		$messages = $this->findEntities($select);

		if (empty($messages)) {
			return [];
		}

		$ids = array_map(static function (LocalMessage $message) {
			return $message->getId();
		}, $messages);

		$attachments = $this->attachmentMapper->findByLocalMessageIds($ids);
		$recipients = $this->recipientMapper->findByLocalMessageIds($ids);

		$recipientMap = [];
		foreach ($recipients as $r) {
			$recipientMap[$r->getLocalMessageId()][] = $r;
		}
		$attachmentMap = [];
		foreach ($attachments as $a) {
			$attachmentMap[$a->getLocalMessageId()][] = $a;
		}

		return array_map(static function ($localMessage) use ($attachmentMap, $recipientMap) {
			$localMessage->setAttachments($attachmentMap[$localMessage->getId()] ?? []);
			$localMessage->setRecipients($recipientMap[$localMessage->getId()] ?? []);
			return $localMessage;
		}, $messages);
	}

	/**
	 * Find all messages that should be sent
	 *
	 * @param int $time upper bound send time stamp
	 *
	 * @return LocalMessage[]
	 */
	public function findDueDrafts(int $time): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->isNotNull('send_at'),
				$qb->expr()->eq('type', $qb->createNamedParameter(LocalMessage::TYPE_DRAFT, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->lte('updated_at', $qb->createNamedParameter($time - 300, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->orX(
					$qb->expr()->isNull('failed'),
					$qb->expr()->eq('failed', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				)
			)
			->orderBy('updated_at', 'asc')
			->orderBy('account_id', 'asc');
		$messages = $this->findEntities($select);

		if (empty($messages)) {
			return [];
		}

		$ids = array_map(static function (LocalMessage $message) {
			return $message->getId();
		}, $messages);

		$attachments = $this->attachmentMapper->findByLocalMessageIds($ids);
		$recipients = $this->recipientMapper->findByLocalMessageIds($ids);

		$recipientMap = [];
		foreach ($recipients as $r) {
			$recipientMap[$r->getLocalMessageId()][] = $r;
		}
		$attachmentMap = [];
		foreach ($attachments as $a) {
			$attachmentMap[$a->getLocalMessageId()][] = $a;
		}

		return array_map(static function ($localMessage) use ($attachmentMap, $recipientMap) {
			$localMessage->setAttachments($attachmentMap[$localMessage->getId()] ?? []);
			$localMessage->setRecipients($recipientMap[$localMessage->getId()] ?? []);
			return $localMessage;
		}, $messages);
	}

	/**
	 * @param Recipient[] $to
	 * @param Recipient[] $cc
	 * @param Recipient[] $bcc
	 */
	public function saveWithRecipients(LocalMessage $message, array $to, array $cc, array $bcc): LocalMessage {
		$this->db->beginTransaction();
		try {
			$message = $this->insert($message);
			$this->recipientMapper->saveRecipients($message->getId(), $to);
			$this->recipientMapper->saveRecipients($message->getId(), $cc);
			$this->recipientMapper->saveRecipients($message->getId(), $bcc);
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
		$message->setRecipients(array_merge(
			$to,
			$cc,
			$bcc,
		));
		return $message;
	}

	/**
	 * @param Recipient[] $to
	 * @param Recipient[] $cc
	 * @param Recipient[] $bcc
	 */
	public function updateWithRecipients(LocalMessage $message, array $to, array $cc, array $bcc): LocalMessage {
		$this->db->beginTransaction();
		try {
			$message = $this->update($message);

			$this->recipientMapper->updateRecipients($message->getId(), $message->getRecipients(), $to, $cc, $bcc);
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
		$recipients = $this->recipientMapper->findByLocalMessageId($message->getId());
		$message->setRecipients($recipients);
		return $message;
	}

	public function deleteWithRecipients(LocalMessage $message): void {
		$this->db->beginTransaction();
		try {
			$this->recipientMapper->deleteForLocalMessage($message->getId());
			$this->delete($message);
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}
}
