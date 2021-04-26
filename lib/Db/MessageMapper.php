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

use OCP\IUser;
use function ltrim;
use OCA\Mail\Account;
use OCA\Mail\Address;
use RuntimeException;
use OCP\IDBConnection;
use function array_map;
use function get_class;
use function mb_strcut;
use function array_keys;
use function array_combine;
use function array_udiff;
use OCA\Mail\AddressList;
use OCA\Mail\Service\Search\Flag;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\Mail\Service\Search\FlagExpression;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use function OCA\Mail\array_flat_map;

/**
 * @template-extends QBMapper<Message>
 */
class MessageMapper extends QBMapper {

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var TagMapper */
	private $tagMapper;

	/** @var PerformanceLogger */
	private $performanceLogger;

	public function __construct(IDBConnection $db,
								ITimeFactory $timeFactory,
								TagMapper $tagMapper,
								PerformanceLogger $performanceLogger) {
		parent::__construct($db, 'mail_messages');
		$this->timeFactory = $timeFactory;
		$this->tagMapper = $tagMapper;
		$this->performanceLogger = $performanceLogger;
	}

	/**
	 * @param IQueryBuilder $query
	 *
	 * @return int[]
	 */
	private function findUids(IQueryBuilder $query): array {
		$result = $query->execute();
		$uids = array_map(function (array $row) {
			return (int)$row['uid'];
		}, $result->fetchAll());
		$result->closeCursor();

		return $uids;
	}

	/**
	 * @param IQueryBuilder $query
	 *
	 * @return int[]
	 */
	private function findIds(IQueryBuilder $query): array {
		$result = $query->execute();
		$uids = array_map(function (array $row) {
			return (int)$row['id'];
		}, $result->fetchAll());
		$result->closeCursor();

		return $uids;
	}

	public function findHighestUid(Mailbox $mailbox): ?int {
		$query = $this->db->getQueryBuilder();

		$query->select($query->func()->max('uid'))
			->from($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$result = $query->execute();
		$max = (int)$result->fetchColumn();
		$result->closeCursor();

		if ($max === 0) {
			return null;
		}
		return $max;
	}

	public function findByUserId(string $userId, int $id): Message {
		$query = $this->db->getQueryBuilder();

		$query->select('m.*')
			->from($this->getTableName(), 'm')
			->join('m', 'mail_mailboxes', 'mb', $query->expr()->eq('m.mailbox_id', 'mb.id', IQueryBuilder::PARAM_INT))
			->join('m', 'mail_accounts', 'a', $query->expr()->eq('mb.account_id', 'a.id', IQueryBuilder::PARAM_INT))
			->where(
				$query->expr()->eq('a.user_id', $query->createNamedParameter($userId)),
				$query->expr()->eq('m.id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);

		$results = $this->findRelatedData($this->findEntities($query), $userId);
		if (empty($results)) {
			throw new DoesNotExistException("Message $id does not exist");
		}
		return $results[0];
	}

	public function findAllUids(Mailbox $mailbox): array {
		$query = $this->db->getQueryBuilder();

		$query->select('uid')
			->from($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));

		return $this->findUids($query);
	}

	public function findAllIds(Mailbox $mailbox): array {
		$query = $this->db->getQueryBuilder();

		$query->select('id')
			->from($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));

		return $this->findIds($query);
	}

	/**
	 * @param Mailbox $mailbox
	 * @param int[] $ids
	 *
	 * @return int[]
	 */
	public function findUidsForIds(Mailbox $mailbox, array $ids) {
		if (empty($ids)) {
			// Shortcut for empty sets
			return [];
		}

		$query = $this->db->getQueryBuilder();
		$query->select('uid')
			->from($this->getTableName())
			->where(
				$query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$query->expr()->in('id', $query->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY)
			);

		return array_flat_map(function (array $chunk) use ($query) {
			$query->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			return $this->findUids($query);
		}, array_chunk($ids, 1000));
	}

	/**
	 * @param Account $account
	 *
	 * @return DatabaseMessage[]
	 */
	public function findThreadingData(Account $account): array {
		$mailboxesQuery = $this->db->getQueryBuilder();
		$messagesQuery = $this->db->getQueryBuilder();

		$mailboxesQuery->select('id')
			->from('mail_mailboxes')
			->where($mailboxesQuery->expr()->eq('account_id', $messagesQuery->createNamedParameter($account->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		$messagesQuery->select('id', 'subject', 'message_id', 'in_reply_to', 'references', 'thread_root_id')
			->from($this->getTableName())
			->where($messagesQuery->expr()->in('mailbox_id', $messagesQuery->createFunction($mailboxesQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($messagesQuery->expr()->isNotNull('message_id'));

		$result = $messagesQuery->execute();
		$messages = array_map(function (array $row) {
			return DatabaseMessage::fromRowData(
				(int)$row['id'],
				$row['subject'],
				$row['message_id'],
				$row['references'],
				$row['in_reply_to'],
				$row['thread_root_id']
			);
		}, $result->fetchAll());
		$result->closeCursor();

		return $messages;
	}

	/**
	 * @param DatabaseMessage[] $messages
	 *
	 * @todo combine threads and send just one query per thread, like UPDATE ... SET thread_root_id = xxx where UID IN (...)
	 */
	public function writeThreadIds(array $messages): void {
		$this->db->beginTransaction();

		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('thread_root_id', $query->createParameter('thread_root_id'))
			->where($query->expr()->eq('id', $query->createParameter('id')));

		foreach ($messages as $message) {
			$query->setParameter(
				'thread_root_id',
				$message->getThreadRootId(),
				$message->getThreadRootId() === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR
			);
			$query->setParameter('id', $message->getDatabaseId(), IQueryBuilder::PARAM_INT);

			$query->execute();
		}

		$this->db->commit();
	}

	/**
	 * @param Message ...$messages
	 * @return void
	 */
	public function insertBulk(Account $account, Message ...$messages): void {
		$this->db->beginTransaction();

		$qb1 = $this->db->getQueryBuilder();
		$qb1->insert($this->getTableName());
		$qb1->setValue('uid', $qb1->createParameter('uid'));
		$qb1->setValue('message_id', $qb1->createParameter('message_id'));
		$qb1->setValue('references', $qb1->createParameter('references'));
		$qb1->setValue('in_reply_to', $qb1->createParameter('in_reply_to'));
		$qb1->setValue('thread_root_id', $qb1->createParameter('thread_root_id'));
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
		$qb1->setValue('flag_mdnsent', $qb1->createParameter('flag_mdnsent'));
		$qb2 = $this->db->getQueryBuilder();

		$qb2->insert('mail_recipients')
			->setValue('message_id', $qb2->createParameter('message_id'))
			->setValue('type', $qb2->createParameter('type'))
			->setValue('label', $qb2->createParameter('label'))
			->setValue('email', $qb2->createParameter('email'));

		foreach ($messages as $message) {
			$qb1->setParameter('uid', $message->getUid(), IQueryBuilder::PARAM_INT);
			$qb1->setParameter('message_id', $message->getMessageId(), IQueryBuilder::PARAM_STR);
			$inReplyTo = $message->getInReplyTo();
			$qb1->setParameter('in_reply_to', $inReplyTo, $inReplyTo === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR);
			$references = $message->getReferences();
			$qb1->setParameter('references', $references, $references === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR);
			$threadRootId = $message->getThreadRootId();
			$qb1->setParameter('thread_root_id', $threadRootId, $threadRootId === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR);
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
			$qb1->setParameter('flag_mdnsent', $message->getFlagMdnsent(), IQueryBuilder::PARAM_BOOL);

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
					$qb2->setParameter('label', mb_strcut($recipient->getLabel(), 0, 255), IQueryBuilder::PARAM_STR);
					$qb2->setParameter('email', $recipient->getEmail(), IQueryBuilder::PARAM_STR);

					$qb2->execute();
				}
			}
			foreach ($message->getTags() as $tag) {
				$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
			}
		}

		$this->db->commit();
	}

	/**
	 * @param Account $account
	 * @param Message[] $messages
	 * @return Message[]
	 */
	public function updateBulk(Account $account, Message ...$messages): array {
		$this->db->beginTransaction();

		$perf = $this->performanceLogger->start(
			'partial sync ' . $account->getId() . ':' . $account->getName()
		);

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
			->set('flag_mdnsent', $query->createParameter('flag_mdnsent'))
			->set('flag_important', $query->createParameter('flag_important'))
			->set('updated_at', $query->createNamedParameter($this->timeFactory->getTime()))
			->where($query->expr()->andX(
				$query->expr()->eq('uid', $query->createParameter('uid')),
				$query->expr()->eq('mailbox_id', $query->createParameter('mailbox_id'))
			));
		// get all tags before the loop and create a mapping [message_id => [tag,...]]
		$tags = $this->tagMapper->getAllTagsForMessages($messages, $account->getUserId());
		$perf->step("Selected Tags for all messages");
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
			$query->setParameter('flag_mdnsent', $message->getFlagMdnsent(), IQueryBuilder::PARAM_BOOL);
			$query->setParameter('flag_important', $message->getFlagImportant(), IQueryBuilder::PARAM_BOOL);

			$query->execute();
			$perf->step('Updated message ' . $message->getId());


			$imapTags = $message->getTags();
			$dbTags = $tags[$message->getMessageId()] ?? [];

			if (empty($imapTags) === true && empty($dbTags) === true) {
				// neither old nor new tags
				continue;
			}

			$toAdd = array_udiff($imapTags, $dbTags, function (Tag $a, Tag $b) {
				return strcmp($a->getImapLabel(), $b->getImapLabel());
			});
			foreach ($toAdd as $tag) {
				// add logging
				$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
			}
			$perf->step("Tagged messages");

			if (empty($dbTags) === true) {
				// we have nothing to possibly remove
				continue;
			}

			$toRemove = array_udiff($dbTags, $imapTags, function (Tag $a, Tag $b) {
				return strcmp($a->getImapLabel(), $b->getImapLabel());
			});
			foreach ($toRemove as $tag) {
				//add logging
				$this->tagMapper->untagMessage($tag, $message->getMessageId());
			}
			$perf->step("Untagged messages");
		}

		$this->db->commit();
		$perf->end();

		return $messages;
	}

	public function getTags(Message $message) : array {
		$mqb = $this->db->getQueryBuilder();
		$mqb->select('tag_id')
			->from('mail_message_tags')
			->where($mqb->expr()->eq('imap_message_id', $mqb->createNamedParameter($message->getMessageId())));
		$result = $mqb->execute();
		$ids = array_map(function (array $row) {
			return (int)$row['tag_id'];
		}, $result->fetchAll());

		return $ids;
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
		$messageIdQuery = $this->db->getQueryBuilder();
		$messageIdQuery->select('id')
			->from($this->getTableName())
			->where($messageIdQuery->expr()->eq('mailbox_id', $messageIdQuery->createNamedParameter($mailbox->getId())));

		$cursor = $messageIdQuery->execute();
		$messageIds = $cursor->fetchAll();
		$cursor->closeCursor();

		$messageIds = array_map(function (array $row) {
			return (int)$row['id'];
		}, $messageIds);

		// delete all related recipient entries
		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery->delete('mail_recipients')
			->where($deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createNamedParameter($messageIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$deleteRecipientsQuery->execute();

		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$query->execute();
	}

	public function deleteByUid(Mailbox $mailbox, int ...$uids): void {
		$messageIdQuery = $this->db->getQueryBuilder();
		$messageIdQuery->select('id')
			->from($this->getTableName())
			->where(
				$messageIdQuery->expr()->eq('mailbox_id', $messageIdQuery->createNamedParameter($mailbox->getId())),
				$messageIdQuery->expr()->in('uid', $messageIdQuery->createNamedParameter($uids, IQueryBuilder::PARAM_INT_ARRAY))
			);

		$cursor = $messageIdQuery->execute();
		$messageIds = $cursor->fetchAll();
		$cursor->closeCursor();

		$messageIds = array_map(function (array $row) {
			return (int)$row['id'];
		}, $messageIds);

		// delete all related recipient entries
		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery->delete('mail_recipients')
			->where($deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createNamedParameter($messageIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$deleteRecipientsQuery->execute();

		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where(
				$query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())),
				$query->expr()->in('uid', $query->createNamedParameter($uids, IQueryBuilder::PARAM_INT_ARRAY))
			);

		$query->execute();
	}

	/**
	 * @param Account $account
	 * @param int $messageId
	 *
	 * @return Message[]
	 */
	public function findThread(Account $account, int $messageId): array {
		$qb = $this->db->getQueryBuilder();
		$subQb1 = $this->db->getQueryBuilder();

		$mailboxIdsQuery = $subQb1
			->select('id')
			->from('mail_mailboxes')
			->where($qb->expr()->eq('account_id', $qb->createNamedParameter($account->getId(), IQueryBuilder::PARAM_INT)));

		/**
		 * Select the message with the given ID or any that has the same thread ID
		 */
		$selectMessages = $qb
			->select('m2.*')
			->from($this->getTableName(), 'm1')
			->leftJoin('m1', $this->getTableName(), 'm2',  $qb->expr()->eq('m1.thread_root_id', 'm2.thread_root_id'))
			->where(
				$qb->expr()->in('m1.mailbox_id', $qb->createFunction($mailboxIdsQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
				$qb->expr()->in('m2.mailbox_id', $qb->createFunction($mailboxIdsQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
				$qb->expr()->eq('m1.id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->isNotNull('m1.thread_root_id')
			)
			->orderBy('sent_at', 'desc');
		return $this->findRelatedData($this->findEntities($selectMessages), $account->getUserId());
	}

	/**
	 * @param Mailbox $mailbox
	 * @param SearchQuery $query
	 * @param int|null $limit
	 * @param int[]|null $uids
	 *
	 * @return int[]
	 */
	public function findIdsByQuery(Mailbox $mailbox, SearchQuery $query, ?int $limit, array $uids = null): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->selectDistinct('m.id')
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

		if (!empty($query->getSubjects())) {
			$select->andWhere(
				$qb->expr()->orX(
					...array_map(function (string $subject) use ($qb) {
						return $qb->expr()->iLike(
							'subject',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($subject) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						);
					}, $query->getSubjects())
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
		foreach ($query->getFlags() as $flag) {
			$select->andWhere($qb->expr()->eq($this->flagToColumnName($flag), $qb->createNamedParameter($flag->isSet(), IQueryBuilder::PARAM_BOOL)));
		}
		if (!empty($query->getFlagExpressions())) {
			$select->andWhere(
				...array_map(function (FlagExpression $expr) use ($select) {
					return $this->flagExpressionToQuery($expr, $select);
				}, $query->getFlagExpressions())
			);
		}

		$select = $select
			->orderBy('sent_at', 'desc');

		if ($limit !== null) {
			$select = $select->setMaxResults($limit);
		}

		return array_map(function (Message $message) {
			return $message->getId();
		}, $this->findEntities($select));
	}

	public function findIdsGloballyByQuery(IUser $user, SearchQuery $query, ?int $limit, array $uids = null): array {
		$qb = $this->db->getQueryBuilder();
		$qbMailboxes = $this->db->getQueryBuilder();

		$select = $qb
			->selectDistinct('m.id')
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

		$selectMailboxIds = $qbMailboxes->select('mb.id')
			->from('mail_mailboxes', 'mb')
			->join('mb', 'mail_accounts', 'a', $qb->expr()->eq('a.id', 'mb.account_id', IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('a.user_id', $qb->createNamedParameter($user->getUID())));
		$select->where(
			$qb->expr()->in('mailbox_id', $qb->createFunction($selectMailboxIds->getSQL()), IQueryBuilder::PARAM_INT_ARRAY)
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

		if (!empty($query->getSubjects())) {
			$select->andWhere(
				$qb->expr()->orX(
					...array_map(function (string $subject) use ($qb) {
						return $qb->expr()->iLike(
							'subject',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($subject) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						);
					}, $query->getSubjects())
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
		foreach ($query->getFlags() as $flag) {
			$select->andWhere($qb->expr()->eq($this->flagToColumnName($flag), $qb->createNamedParameter($flag->isSet(), IQueryBuilder::PARAM_BOOL)));
		}
		if (!empty($query->getFlagExpressions())) {
			$select->andWhere(
				...array_map(function (FlagExpression $expr) use ($select) {
					return $this->flagExpressionToQuery($expr, $select);
				}, $query->getFlagExpressions())
			);
		}

		$select = $select
			->orderBy('sent_at', 'desc');

		if ($limit !== null) {
			$select = $select->setMaxResults($limit);
		}

		return array_map(function (Message $message) {
			return $message->getId();
		}, $this->findEntities($select));
	}

	private function flagExpressionToQuery(FlagExpression $expr, IQueryBuilder $qb): string {
		$operands = array_map(function (object $operand) use ($qb) {
			if ($operand instanceof Flag) {
				return $qb->expr()->eq(
					$this->flagToColumnName($operand),
					$qb->createNamedParameter($operand->isSet(), IQueryBuilder::PARAM_BOOL),
					IQueryBuilder::PARAM_BOOL
				);
			}
			if ($operand instanceof FlagExpression) {
				return $this->flagExpressionToQuery($operand, $qb);
			}

			throw new RuntimeException('Invalid operand type ' . get_class($operand));
		}, $expr->getOperands());

		switch ($expr->getOperator()) {
			case 'and':
				/** @psalm-suppress InvalidCast */
				return (string) $qb->expr()->andX(...$operands);
			case 'or':
				/** @psalm-suppress InvalidCast */
				return (string) $qb->expr()->orX(...$operands);
			default:
				throw new RuntimeException('Unknown operator ' . $expr->getOperator());
		}
	}

	private function flagToColumnName(Flag $flag): string {
		// workaround for @link https://github.com/nextcloud/mail/issues/25
		if ($flag->getFlag() === Tag::LABEL_IMPORTANT) {
			return "flag_important";
		}
		$key = ltrim($flag->getFlag(), '\\$');
		return "flag_$key";
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
	 * @param int[] $ids
	 *
	 * @return Message[]
	 */
	public function findByIds(string $userId, array $ids): array {
		if (empty($ids)) {
			return [];
		}
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			)
			->orderBy('sent_at', 'desc');

		return $this->findRelatedData($this->findEntities($select), $userId);
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
	 * @param Message[] $messages
	 * @return Message[]
	 */
	public function findRelatedData(array $messages, string $userId): array {
		$messages = $this->findRecipients($messages);
		$tags = $this->tagMapper->getAllTagsForMessages($messages, $userId);
		/** @var Message $message */
		$messages = array_map(function ($message) use ($tags) {
			$message->setTags($tags[$message->getMessageId()] ?? []);
			return $message;
		}, $messages);
		return $messages;
	}

	/**
	 * @param Mailbox $mailbox
	 * @param int $highest
	 *
	 * @return int[]
	 */
	public function findNewIds(Mailbox $mailbox, array $ids): array {
		$qb = $this->db->getQueryBuilder();
		$sub = $this->db->getQueryBuilder();

		$subSelect = $sub
			->select($sub->func()->max('uid'))
			->from($this->getTableName())
			->where(
				$sub->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$sub->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);
		$select = $qb
			->select('id')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->gt('uid', $qb->createFunction('(' . $subSelect->getSQL() . ')'), IQueryBuilder::PARAM_INT)
			);

		return $this->findIds($select);
	}

	/**
	 * Currently unused
	 */
	public function findChanged(Account $account, Mailbox $mailbox, int $since): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->gt('updated_at', $qb->createNamedParameter($since, IQueryBuilder::PARAM_INT))
			);
		// TODO: change this to findRelatedData
		return $this->findRelatedData($this->findEntities($select), $account->getUserId());
	}

	/**
	 * @param array $mailboxIds
	 * @param int $limit
	 *
	 * @return Message[]
	 */
	public function findLatestMessages(string $userId, array $mailboxIds, int $limit): array {
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

		return $this->findRelatedData($this->findEntities($select), $userId);
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

	public function getIdForUid(Mailbox $mailbox, $uid): ?int {
		$qb = $this->db->getQueryBuilder();

		$select = $qb
			->select('m.id')
			->from($this->getTableName(), 'm')
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId()), IQueryBuilder::PARAM_INT),
				$qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT)
			);
		$result = $select->execute();
		$rows = $result->fetchAll();
		if (empty($rows)) {
			return null;
		}
		return (int) $rows[0]['id'];
	}

	/**
	 * @return Message[]
	 */
	public function findWithEmptyMessageId(): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->isNull('message_id')
			);

		return $this->findEntities($select);
	}
}
