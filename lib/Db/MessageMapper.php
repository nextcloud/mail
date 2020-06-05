<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use Horde_Imap_Client;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use function array_combine;
use function array_keys;
use function array_map;
use function mb_substr;

class MessageMapper extends QBMapper {

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(IDBConnection $db,
								ITimeFactory $timeFactory) {
		parent::__construct($db, 'mail_messages');
		$this->timeFactory = $timeFactory;
	}

	private function findUids(IQueryBuilder $query): array {
		$result = $query->execute();
		$uids = array_map(function (array $row) {
			return (int)$row['uid'];
		}, $result->fetchAll());
		$result->closeCursor();

		return $uids;
	}

	public function findHighestUid(Mailbox $mailbox): ?int {
		$query = $this->db->getQueryBuilder();

		$query->select($query->createFunction('MAX(' . $query->getColumnName('uid') . ')'))
			->from($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$result = $query->execute();
		$max = (int)$result->fetchColumn(0);
		$result->closeCursor();

		if ($max === 0) {
			return null;
		}
		return $max;
	}

	public function findAllUids(Mailbox $mailbox): array {
		$query = $this->db->getQueryBuilder();

		$query->select('uid')
			->from($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		return $this->findUids($query);
	}

	public function insertBulk(Message ...$messages): void {
		$this->db->beginTransaction();

		$qb1 = $this->db->getQueryBuilder();
		$qb1->insert($this->getTableName());
		$qb1->setValue('uid', $qb1->createParameter('uid'));
		$qb1->setValue('message_id', $qb1->createParameter('message_id'));
		$qb1->setValue('mailbox_id', $qb1->createParameter('mailbox_id'));
		$qb1->setValue('subject', $qb1->createParameter('subject'));
		$qb1->setValue('sent_at', $qb1->createParameter('sent_at'));
		$qb1->setValue('flag_answered', $qb1->createParameter('flag_answered'));
		$qb1->setValue('flag_deleted', $qb1->createParameter('flag_deleted'));
		$qb1->setValue('flag_draft', $qb1->createParameter('flag_draft'));
		$qb1->setValue('flag_flagged', $qb1->createParameter('flag_flagged'));
		$qb1->setValue('flag_seen', $qb1->createParameter('flag_seen'));
		$qb1->setValue('flag_forwarded', $qb1->createParameter('flag_forwarded'));
		$qb1->setValue('flag_junk', $qb1->createParameter('flag_junk'));
		$qb1->setValue('flag_notjunk', $qb1->createParameter('flag_notjunk'));
		$qb1->setValue('flag_important', $qb1->createParameter('flag_important'));
		$qb2 = $this->db->getQueryBuilder();

		$qb2->insert('mail_recipients')
			->setValue('message_id', $qb2->createParameter('message_id'))
			->setValue('type', $qb2->createParameter('type'))
			->setValue('label', $qb2->createParameter('label'))
			->setValue('email', $qb2->createParameter('email'));

		foreach ($messages as $message) {
			$qb1->setParameter('uid', $message->getUid(), IQueryBuilder::PARAM_INT);
			$qb1->setParameter('message_id', $message->getMessageId(), IQueryBuilder::PARAM_STR);
			$qb1->setParameter('mailbox_id', $message->getMailboxId(), IQueryBuilder::PARAM_INT);
			$qb1->setParameter('subject', $message->getSubject(), IQueryBuilder::PARAM_STR);
			$qb1->setParameter('sent_at', $message->getSentAt(), IQueryBuilder::PARAM_INT);
			$qb1->setParameter('flag_answered', $message->getFlagAnswered(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_deleted', $message->getFlagDeleted(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_draft', $message->getFlagDraft(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_flagged', $message->getFlagFlagged(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_seen', $message->getFlagSeen(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_forwarded', $message->getFlagForwarded(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_junk', $message->getFlagJunk(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_notjunk', $message->getFlagNotjunk(), IQueryBuilder::PARAM_BOOL);
			$qb1->setParameter('flag_important', $message->getFlagImportant(), IQueryBuilder::PARAM_BOOL);

			$qb1->execute();

			$messageId = $qb1->getLastInsertId();
			$recipientTypes = [
				Address::TYPE_FROM => $message->getFrom(),
				Address::TYPE_TO => $message->getTo(),
				Address::TYPE_CC => $message->getCc(),
				Address::TYPE_BCC => $message->getBcc(),
			];
			foreach ($recipientTypes as $type => $recipients) {
				/** @var AddressList $recipients */
				foreach ($recipients->iterate() as $recipient) {
					/** @var Address $recipient */
					if ($recipient->getEmail() === null) {
						// If for some reason the e-mail is not set we should ignore this entry
						continue;
					}

					$qb2->setParameter('message_id', $messageId, IQueryBuilder::PARAM_INT);
					$qb2->setParameter('type', $type, IQueryBuilder::PARAM_INT);
					$qb2->setParameter('label', mb_substr($recipient->getLabel(), 0, 255), IQueryBuilder::PARAM_STR);
					$qb2->setParameter('email', $recipient->getEmail(), IQueryBuilder::PARAM_STR);

					$qb2->execute();
				}
			}
		}

		$this->db->commit();
	}

	/**
	 * @param Message ...$messages
	 *
	 * @return Message[]
	 */
	public function updateBulk(Message ...$messages): array {
		$this->db->beginTransaction();

		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('flag_answered', $query->createParameter('flag_answered'))
			->set('flag_deleted', $query->createParameter('flag_deleted'))
			->set('flag_draft', $query->createParameter('flag_draft'))
			->set('flag_flagged', $query->createParameter('flag_flagged'))
			->set('flag_seen', $query->createParameter('flag_seen'))
			->set('flag_forwarded', $query->createParameter('flag_forwarded'))
			->set('flag_junk', $query->createParameter('flag_junk'))
			->set('flag_notjunk', $query->createParameter('flag_notjunk'))
			->set('updated_at', $query->createNamedParameter($this->timeFactory->getTime()))
			->where($query->expr()->andX(
				$query->expr()->eq('uid', $query->createParameter('uid')),
				$query->expr()->eq('mailbox_id', $query->createParameter('mailbox_id'))
			));

		foreach ($messages as $message) {
			if (empty($message->getUpdatedFields())) {
				// Micro optimization
				continue;
			}

			$query->setParameter('uid', $message->getUid(), IQueryBuilder::PARAM_INT);
			$query->setParameter('mailbox_id', $message->getMailboxId(), IQueryBuilder::PARAM_INT);
			$query->setParameter('flag_answered', $message->getFlagAnswered(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_deleted', $message->getFlagDeleted(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_draft', $message->getFlagDraft(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_flagged', $message->getFlagFlagged(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_seen', $message->getFlagSeen(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_forwarded', $message->getFlagForwarded(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_junk', $message->getFlagJunk(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_notjunk', $message->getFlagNotjunk(), IQueryBuilder::PARAM_BOOL);

			$query->execute();
		}

		$this->db->commit();

		return $messages;
	}

	/**
	 * @param Message ...$messages
	 *
	 * @return Message[]
	 */
	public function updatePreviewDataBulk(Message ...$messages): array {
		$this->db->beginTransaction();

		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('flag_attachments', $query->createParameter('flag_attachments'))
			->set('preview_text', $query->createParameter('preview_text'))
			->set('structure_analyzed', $query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
			->set('updated_at', $query->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
			->where($query->expr()->andX(
				$query->expr()->eq('uid', $query->createParameter('uid')),
				$query->expr()->eq('mailbox_id', $query->createParameter('mailbox_id'))
			));

		foreach ($messages as $message) {
			if (empty($message->getUpdatedFields())) {
				// Micro optimization
				continue;
			}

			$query->setParameter('uid', $message->getUid(), IQueryBuilder::PARAM_INT);
			$query->setParameter('mailbox_id', $message->getMailboxId(), IQueryBuilder::PARAM_INT);
			$query->setParameter('flag_attachments', $message->getFlagAttachments(), $message->getFlagAttachments() === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_BOOL);
			$query->setParameter('preview_text', $message->getPreviewText(), $message->getPreviewText() === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR);

			$query->execute();
		}

		$this->db->commit();

		return $messages;
	}

	public function deleteAll(Mailbox $mailbox): void {
		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$query->execute();
	}

	public function deleteByUid(Mailbox $mailbox, int ...$uids): void {
		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where(
				$query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())),
				$query->expr()->in('uid', $query->createNamedParameter($uids, IQueryBuilder::PARAM_INT_ARRAY))
			);

		$query->execute();
	}

	/**
	 * @param Mailbox $mailbox
	 * @param SearchQuery $query
	 * @param int|null $limit
	 * @param int[]|null $uids
	 *
	 * @return int[]
	 */
	public function findUidsByQuery(Mailbox $mailbox, SearchQuery $query, ?int $limit, array $uids = null): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->selectDistinct('m.uid')
			->addSelect('m.sent_at')
			->from($this->getTableName(), 'm');

		if (!empty($query->getFrom())) {
			$select->innerJoin('m', 'mail_recipients', 'r0', 'm.id = r0.message_id');
		}
		if (!empty($query->getTo())) {
			$select->innerJoin('m', 'mail_recipients', 'r1', 'm.id = r1.message_id');
		}
		if (!empty($query->getCc())) {
			$select->innerJoin('m', 'mail_recipients', 'r2', 'm.id = r2.message_id');
		}
		if (!empty($query->getBcc())) {
			$select->innerJoin('m', 'mail_recipients', 'r3', 'm.id = r3.message_id');
		}

		$select->where(
			$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId()), IQueryBuilder::PARAM_INT)
		);

		if (!empty($query->getFrom())) {
			$select->andWhere(
				$qb->expr()->in('r0.email', $qb->createNamedParameter($query->getFrom(), IQueryBuilder::PARAM_STR_ARRAY))
			);
		}
		if (!empty($query->getTo())) {
			$select->andWhere(
				$qb->expr()->in('r1.email', $qb->createNamedParameter($query->getTo(), IQueryBuilder::PARAM_STR_ARRAY))
			);
		}
		if (!empty($query->getCc())) {
			$select->andWhere(
				$qb->expr()->in('r2.email', $qb->createNamedParameter($query->getCc(), IQueryBuilder::PARAM_STR_ARRAY))
			);
		}
		if (!empty($query->getBcc())) {
			$select->andWhere(
				$qb->expr()->in('r3.email', $qb->createNamedParameter($query->getBcc(), IQueryBuilder::PARAM_STR_ARRAY))
			);
		}

		if ($query->getSubject() !== null) {
			$select->andWhere(
				$qb->expr()->iLike(
					'subject',
					$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query-> $query->getSubject()) . '%', IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR
				)
			);
		}

		if ($query->getCursor() !== null) {
			$select->andWhere(
				$qb->expr()->lt('sent_at', $qb->createNamedParameter($query->getCursor(), IQueryBuilder::PARAM_INT))
			);
		}
		if ($uids !== null) {
			$select->andWhere(
				$qb->expr()->in('uid', $qb->createNamedParameter($uids, IQueryBuilder::PARAM_INT_ARRAY))
			);
		}

		$flags = $query->getFlags();
		$flagKeys = array_keys($flags);
		foreach ([
			Horde_Imap_Client::FLAG_ANSWERED,
			Horde_Imap_Client::FLAG_DELETED,
			Horde_Imap_Client::FLAG_DRAFT,
			Horde_Imap_Client::FLAG_FLAGGED,
			Horde_Imap_Client::FLAG_RECENT,
			Horde_Imap_Client::FLAG_SEEN,
			Horde_Imap_Client::FLAG_FORWARDED,
			Horde_Imap_Client::FLAG_JUNK,
			Horde_Imap_Client::FLAG_NOTJUNK,
			'\\important',
		] as $flag) {
			if (in_array($flag, $flagKeys, true)) {
				$key = ltrim($flag, '\\');
				$select->andWhere($qb->expr()->eq("flag_$key", $qb->createNamedParameter($flags[$flag], IQueryBuilder::PARAM_BOOL)));
			}
		}

		$select = $select
			->orderBy('sent_at', 'desc');

		if ($limit !== null) {
			$select = $select->setMaxResults($limit);
		}

		return array_map(function (Message $message) {
			return $message->getUid();
		}, $this->findEntities($select));
	}

	/**
	 * @param Mailbox $mailbox
	 * @param int[] $uids
	 *
	 * @return Message[]
	 */
	public function findByUids(Mailbox $mailbox, array $uids): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId()), IQueryBuilder::PARAM_INT),
				$qb->expr()->in('uid', $qb->createNamedParameter($uids, IQueryBuilder::PARAM_INT_ARRAY))
			)
			->orderBy('sent_at', 'desc');

		return $this->findRecipients($this->findEntities($select));
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	private function findRecipients(array $messages): array {
		/** @var Message[] $indexedMessages */
		$indexedMessages = array_combine(
			array_map(function (Message $msg) {
				return $msg->getId();
			}, $messages),
			$messages
		);
		$qb2 = $this->db->getQueryBuilder();
		$qb2->select('label', 'email', 'type', 'message_id')
			->from('mail_recipients')
			->where(
				$qb2->expr()->in('message_id', $qb2->createNamedParameter(array_keys($indexedMessages), IQueryBuilder::PARAM_INT_ARRAY))
			);
		$recipientsResult = $qb2->execute();
		foreach ($recipientsResult->fetchAll() as $recipient) {
			$message = $indexedMessages[(int)$recipient['message_id']];
			switch ($recipient['type']) {
				case Address::TYPE_FROM:
					$message->setFrom(
						$message->getFrom()->merge(AddressList::fromRow($recipient))
					);
					break;
				case Address::TYPE_TO:
					$message->setTo(
						$message->getTo()->merge(AddressList::fromRow($recipient))
					);
					break;
				case Address::TYPE_CC:
					$message->setCc(
						$message->getCc()->merge(AddressList::fromRow($recipient))
					);
					break;
				case Address::TYPE_BCC:
					$message->setFrom(
						$message->getFrom()->merge(AddressList::fromRow($recipient))
					);
					break;
			}
		}
		$recipientsResult->closeCursor();

		return $messages;
	}

	/**
	 * @param Mailbox $mailbox
	 * @param int $highest
	 *
	 * @return Message[]
	 */
	public function findNewUids(Mailbox $mailbox, int $highest): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('uid')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->gt('uid', $qb->createNamedParameter($highest, IQueryBuilder::PARAM_INT))
			);

		return $this->findUids($select);
	}

	public function findChanged(Mailbox $mailbox, int $since): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->gt('updated_at', $qb->createNamedParameter($since, IQueryBuilder::PARAM_INT))
			);

		return $this->findRecipients($this->findEntities($select));
	}

	/**
	 * @param array $mailboxIds
	 * @param int $limit
	 *
	 * @return string[]
	 */
	public function findLatestMessages(array $mailboxIds, int $limit): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('m.*')
			->from($this->getTableName(), 'm')
			->join('m', 'mail_recipients', 'r', $qb->expr()->eq('m.id', 'r.message_id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('r.type', $qb->createNamedParameter(Address::TYPE_FROM, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->in('m.mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			)
			->orderBy('sent_at', 'desc')
			->setMaxResults($limit);

		return $this->findRecipients($this->findEntities($select));
	}

	public function deleteOrphans(): void {
		$qb1 = $this->db->getQueryBuilder();
		$idsQuery = $qb1->select('m.id')
			->from($this->getTableName(), 'm')
			->leftJoin('m', 'mail_mailboxes', 'mb', $qb1->expr()->eq('m.mailbox_id', 'mb.id'))
			->where($qb1->expr()->isNull('mb.id'));
		$result = $idsQuery->execute();
		$ids = array_map(function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY));
		$query->execute();

		$qb3 = $this->db->getQueryBuilder();
		$recipientIdsQuery = $qb3->selectDistinct('r.id')
			->from('mail_recipients', 'r')
			->leftJoin('r', 'mail_messages', 'm', $qb3->expr()->eq('r.message_id', 'm.id'))
			->where($qb3->expr()->isNull('m.id'));
		$result = $recipientIdsQuery->execute();
		$ids = array_map(function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		$qb4 = $this->db->getQueryBuilder();
		$recipientsQuery = $qb4
			->delete('mail_recipients')
			->where($qb4->expr()->in('id', $qb4->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY));
		$recipientsQuery->execute();
	}
}
