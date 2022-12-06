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

use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\Service\Search\Flag;
use OCA\Mail\Service\Search\FlagExpression;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use RuntimeException;
use Throwable;
use function array_chunk;
use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function array_udiff;
use function get_class;
use function ltrim;
use function mb_convert_encoding;
use function mb_strcut;
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
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));

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
		$messages = [];
		while (($row = $result->fetch())) {
			$messages[] = DatabaseMessage::fromRowData(
				(int)$row['id'],
				$row['subject'],
				$row['message_id'],
				$row['references'],
				$row['in_reply_to'],
				$row['thread_root_id']
			);
		}
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

		try {
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
		} catch (Throwable $e) {
			// Make sure to always roll back, otherwise the outer code runs in a failed transaction
			$this->db->rollBack();

			throw $e;
		}
	}

	/**
	 * @param Message ...$messages
	 * @return void
	 */
	public function insertBulk(Account $account, Message ...$messages): void {
		$this->db->beginTransaction();
		try {
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
		} catch (Throwable $e) {
			// Make sure to always roll back, otherwise the outer code runs in a failed transaction
			$this->db->rollBack();

			throw $e;
		}
	}

	/**
	 * @param Account $account
	 * @param bool $permflagsEnabled
	 * @param Message[] $messages
	 * @return Message[]
	 */
	public function updateBulk(Account $account, bool $permflagsEnabled, Message ...$messages): array {
		$this->db->beginTransaction();

		$perf = $this->performanceLogger->start(
			'partial sync ' . $account->getId() . ':' . $account->getName()
		);

		try {
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

			// get all tags before the loop and create a mapping [message_id => [tag,...]] but only if permflags are enabled
			$tags = [];
			if ($permflagsEnabled) {
				$tags = $this->tagMapper->getAllTagsForMessages($messages, $account->getUserId());
				$perf->step("Selected Tags for all messages");
			}

			foreach ($messages as $message) {
				if (empty($message->getUpdatedFields()) === false) {
					// only run if there is anything to actually update
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
				}

				// check permflags and only go through the tagging logic if they're enabled
				if ($permflagsEnabled) {
					$this->updateTags($account, $message, $tags, $perf);
				}
			}

			$this->db->commit();
		} catch (Throwable $e) {
			// Make sure to always roll back, otherwise the outer code runs in a failed transaction
			$this->db->rollBack();

			throw $e;
		}

		$perf->end();

		return $messages;
	}

	/**
	 * @param Account $account
	 * @param Message $message
	 * @param Tag[][] $tags
	 * @param PerformanceLoggerTask $perf
	 */
	private function updateTags(Account $account, Message $message, array $tags, PerformanceLoggerTask $perf): void {
		$imapTags = $message->getTags();
		$dbTags = $tags[$message->getMessageId()] ?? [];

		if (empty($imapTags) && empty($dbTags)) {
			// neither old nor new tags
			return;
		}

		$toAdd = array_udiff($imapTags, $dbTags, static function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toAdd as $tag) {
			$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
		}
		$perf->step("Tagged messages");

		if (empty($dbTags)) {
			// we have nothing to possibly remove
			return;
		}

		$toRemove = array_udiff($dbTags, $imapTags, static function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toRemove as $tag) {
			$this->tagMapper->untagMessage($tag, $message->getMessageId());
		}
		$perf->step("Untagged messages");
	}

	/**
	 * @param Message ...$messages
	 *
	 * @return Message[]
	 */
	public function updatePreviewDataBulk(Message ...$messages): array {
		$this->db->beginTransaction();

		try {
			$query = $this->db->getQueryBuilder();
			$query->update($this->getTableName())
				->set('flag_attachments', $query->createParameter('flag_attachments'))
				->set('preview_text', $query->createParameter('preview_text'))
				->set('structure_analyzed', $query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
				->set('updated_at', $query->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
				->set('imip_message', $query->createParameter('imip_message'))
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
				$previewText = null;
				if ($message->getPreviewText() !== null) {
					$previewText = mb_strcut(mb_convert_encoding($message->getPreviewText(), 'UTF-8', 'UTF-8'), 0, 255);
					// Make sure modifications are visible when these objects are used right away
					$message->setPreviewText($previewText);
				}
				$query->setParameter(
					'preview_text',
					$previewText,
					$previewText === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR
				);
				$query->setParameter('imip_message', $message->isImipMessage(), IQueryBuilder::PARAM_BOOL);

				$query->execute();
			}

			$this->db->commit();
		} catch (Throwable $e) {
			// Make sure to always roll back, otherwise the outer code runs in a failed transaction
			$this->db->rollBack();

			throw $e;
		}

		return $messages;
	}

	/**
	 * @param Message ...$messages
	 *
	 * @return Message[]
	 */
	public function updateImipData(Message ...$messages): array {
		$this->db->beginTransaction();

		try {
			$query = $this->db->getQueryBuilder();
			$query->update($this->getTableName())
				->set('imip_message', $query->createParameter('imip_message'))
				->set('imip_error', $query->createParameter('imip_error'))
				->set('imip_processed', $query->createParameter('imip_processed'))
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
				$query->setParameter('imip_message', $message->isImipMessage(), IQueryBuilder::PARAM_BOOL);
				$query->setParameter('imip_error', $message->isImipError(), IQueryBuilder::PARAM_BOOL);
				$query->setParameter('imip_processed', $message->isImipProcessed(), IQueryBuilder::PARAM_BOOL);
				$query->execute();
			}

			$this->db->commit();
		} catch (Throwable $e) {
			// Make sure to always roll back, otherwise the outer code runs in a failed transaction
			$this->db->rollBack();

			throw $e;
		}

		return $messages;
	}

	public function resetPreviewDataFlag(): void {
		$qb = $this->db->getQueryBuilder();
		$update = $qb->update($this->getTableName())
			->set('structure_analyzed', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL));
		$update->executeStatement();
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

		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery->delete('mail_recipients')
			->where($deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createParameter('ids')));

		foreach (array_chunk($messageIds, 1000) as $chunk) {
			// delete all related recipient entries
			$deleteRecipientsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteRecipientsQuery->execute();
		}

		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$query->execute();
	}

	public function deleteByUid(Mailbox $mailbox, int ...$uids): void {
		$messageIdQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteMessagesQuery = $this->db->getQueryBuilder();

		// Get all message ids query
		$messageIdQuery->select('id')
			->from($this->getTableName())
			->where(
				$messageIdQuery->expr()->eq('mailbox_id', $messageIdQuery->createNamedParameter($mailbox->getId())),
				$messageIdQuery->expr()->in('uid', $messageIdQuery->createParameter('uids'))
			);

		$deleteRecipientsQuery->delete('mail_recipients')
			->where($deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createParameter('messageIds')));

		$deleteMessagesQuery->delete($this->getTableName())
			->where($deleteMessagesQuery->expr()->in('id', $deleteMessagesQuery->createParameter('messageIds')));

		foreach (array_chunk($uids, 1000) as $chunk) {
			$messageIdQuery->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$cursor = $messageIdQuery->execute();

			$messageIds = array_map(function (array $message) {
				return $message['id'];
			}, $cursor->fetchAll());
			$cursor->closeCursor();

			// delete all related recipient entries
			$deleteRecipientsQuery->setParameter('messageIds', $messageIds, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteRecipientsQuery->execute();

			// delete all messages
			$deleteMessagesQuery->setParameter('messageIds', $messageIds, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteMessagesQuery->execute();
		}
	}

	/**
	 * @param Account $account
	 * @param string $threadRootId
	 *
	 * @return Message[]
	 */
	public function findThread(Account $account, string $threadRootId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('messages.*')
			->from($this->getTableName(), 'messages')
			->join('messages', 'mail_mailboxes', 'mailboxes', $qb->expr()->eq('messages.mailbox_id', 'mailboxes.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('mailboxes.account_id', $qb->createNamedParameter($account->getId(), IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('messages.thread_root_id', $qb->createNamedParameter($threadRootId, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			)
			->orderBy('messages.sent_at', 'desc');

		return $this->findRelatedData($this->findEntities($qb), $account->getUserId());
	}

	/**
	 * @param Account $account
	 * @param string $messageId
	 *
	 * @return Message[]
	 */
	public function findByMessageId(Account $account, string $messageId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('messages.*')
			->from($this->getTableName(), 'messages')
			->join('messages', 'mail_mailboxes', 'mailboxes', $qb->expr()->eq('messages.mailbox_id', 'mailboxes.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('mailboxes.account_id', $qb->createNamedParameter($account->getId(), IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->eq('messages.message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			);

		return $this->findEntities($qb);
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

		if ($this->needDistinct($query)) {
			$select = $qb->selectDistinct(['m.id', 'm.sent_at']);
		} else {
			$select = $qb->select(['m.id', 'm.sent_at']);
		}

		$selfJoin = $select->expr()->andX(
			$select->expr()->eq('m.mailbox_id', 'm2.mailbox_id', IQueryBuilder::PARAM_INT),
			$select->expr()->eq('m.thread_root_id', 'm2.thread_root_id', IQueryBuilder::PARAM_INT),
			$select->expr()->lt('m.sent_at', 'm2.sent_at', IQueryBuilder::PARAM_INT)
		);

		$select->from($this->getTableName(), 'm')
			->leftJoin('m', $this->getTableName(), 'm2', $selfJoin);

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
			$qb->expr()->eq('m.mailbox_id', $qb->createNamedParameter($mailbox->getId()), IQueryBuilder::PARAM_INT)
		);

		if (!empty($query->getTags())) {
			$select->innerJoin('m', 'mail_message_tags', 'tags', 'm.message_id = tags.imap_message_id');
			$select->andWhere(
				$qb->expr()->in('tags.tag_id', $qb->createNamedParameter($query->getTags(), IQueryBuilder::PARAM_STR_ARRAY))
			);
		}

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
							'm.subject',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($subject) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						);
					}, $query->getSubjects())
				)
			);
		}

		if (!empty($query->getStart())) {
			$select->andWhere(
				$qb->expr()->gte('m.sent_at', $qb->createNamedParameter($query->getStart()), IQueryBuilder::PARAM_INT)
			);
		}

		if (!empty($query->getEnd())) {
			$select->andWhere(
				$qb->expr()->lte('m.sent_at', $qb->createNamedParameter($query->getEnd()), IQueryBuilder::PARAM_INT)
			);
		}


		if ($query->getHasAttachments()) {
			$select->andWhere(
				$qb->expr()->eq('m.flag_attachments', $qb->createNamedParameter($query->getHasAttachments(), IQueryBuilder::PARAM_INT))
			);
		}

		if ($query->getCursor() !== null) {
			$select->andWhere(
				$qb->expr()->lt('m.sent_at', $qb->createNamedParameter($query->getCursor(), IQueryBuilder::PARAM_INT))
			);
		}

		// createParameter
		if ($uids !== null) {
			$select->andWhere(
				$qb->expr()->in('m.uid', $qb->createParameter('uids'))
			);
		}
		foreach ($query->getFlags() as $flag) {
			$select->andWhere($qb->expr()->eq('m.' . $this->flagToColumnName($flag), $qb->createNamedParameter($flag->isSet(), IQueryBuilder::PARAM_BOOL)));
		}
		if (!empty($query->getFlagExpressions())) {
			$select->andWhere(
				...array_map(function (FlagExpression $expr) use ($select) {
					return $this->flagExpressionToQuery($expr, $select, 'm');
				}, $query->getFlagExpressions())
			);
		}

		$select->andWhere($qb->expr()->isNull('m2.id'));

		$select->orderBy('m.sent_at', 'desc');

		if ($limit !== null) {
			$select->setMaxResults($limit);
		}

		if ($uids !== null) {
			return array_flat_map(function (array $chunk) use ($qb, $select) {
				$qb->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				return array_map(function (Message $message) {
					return $message->getId();
				}, $this->findEntities($select));
			}, array_chunk($uids, 1000));
		}

		return array_map(function (Message $message) {
			return $message->getId();
		}, $this->findEntities($select));
	}

	public function findIdsGloballyByQuery(IUser $user, SearchQuery $query, ?int $limit, array $uids = null): array {
		$qb = $this->db->getQueryBuilder();
		$qbMailboxes = $this->db->getQueryBuilder();

		if ($this->needDistinct($query)) {
			$select = $qb->selectDistinct(['m.id', 'm.sent_at']);
		} else {
			$select = $qb->select(['m.id', 'm.sent_at']);
		}

		$selfJoin = $select->expr()->andX(
			$select->expr()->eq('m.mailbox_id', 'm2.mailbox_id', IQueryBuilder::PARAM_INT),
			$select->expr()->eq('m.thread_root_id', 'm2.thread_root_id', IQueryBuilder::PARAM_INT),
			$select->expr()->lt('m.sent_at', 'm2.sent_at', IQueryBuilder::PARAM_INT)
		);

		$select->from($this->getTableName(), 'm')
			->leftJoin('m', $this->getTableName(), 'm2', $selfJoin);

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
			$qb->expr()->in('m.mailbox_id', $qb->createFunction($selectMailboxIds->getSQL()), IQueryBuilder::PARAM_INT_ARRAY)
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
							'm.subject',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($subject) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						);
					}, $query->getSubjects())
				)
			);
		}

		if ($query->getCursor() !== null) {
			$select->andWhere(
				$qb->expr()->lt('m.sent_at', $qb->createNamedParameter($query->getCursor(), IQueryBuilder::PARAM_INT))
			);
		}
		if ($uids !== null) {
			$select->andWhere(
				$qb->expr()->in('m.uid', $qb->createParameter('uids'))
			);
		}
		foreach ($query->getFlags() as $flag) {
			$select->andWhere($qb->expr()->eq('m.' . $this->flagToColumnName($flag), $qb->createNamedParameter($flag->isSet(), IQueryBuilder::PARAM_BOOL)));
		}
		if (!empty($query->getFlagExpressions())) {
			$select->andWhere(
				...array_map(function (FlagExpression $expr) use ($select) {
					return $this->flagExpressionToQuery($expr, $select, 'm');
				}, $query->getFlagExpressions())
			);
		}

		$select->andWhere($qb->expr()->isNull('m2.id'));

		$select->orderBy('m.sent_at', 'desc');

		if ($limit !== null) {
			$select->setMaxResults($limit);
		}

		if ($uids !== null) {
			return array_flat_map(function (array $chunk) use ($select) {
				$select->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				return array_map(function (Message $message) {
					return $message->getId();
				}, $this->findEntities($select));
			}, array_chunk($uids, 1000));
		}

		return array_map(function (Message $message) {
			return $message->getId();
		}, $this->findEntities($select));
	}

	/**
	 * Return true when a distinct query is required.
	 *
	 * For the threaded message list it's necessary to self-join
	 * the mail_messages table to figure out if we are the latest message
	 * of a thread.
	 *
	 * Unfortunately a self-join on a larger table has a significant
	 * performance impact. An database index (e.g. on thread_root_id)
	 * could improve the query performance but adding an index is blocked by
	 * - https://github.com/nextcloud/server/pull/25471
	 * - https://github.com/nextcloud/mail/issues/4735
	 *
	 * We noticed a better query performance without distinct. As distinct is
	 * only necessary when a search query is present (e.g. search for mail with
	 * two recipients) it's reasonable to use distinct only for those requests.
	 *
	 * @param SearchQuery $query
	 * @return bool
	 */
	private function needDistinct(SearchQuery $query): bool {
		return !empty($query->getFrom())
			|| !empty($query->getTo())
			|| !empty($query->getCc())
			|| !empty($query->getBcc());
	}

	private function flagExpressionToQuery(FlagExpression $expr, IQueryBuilder $qb, string $tableAlias): string {
		$operands = array_map(function (object $operand) use ($qb, $tableAlias) {
			if ($operand instanceof Flag) {
				return $qb->expr()->eq(
					$tableAlias . '.' . $this->flagToColumnName($operand),
					$qb->createNamedParameter($operand->isSet(), IQueryBuilder::PARAM_BOOL),
					IQueryBuilder::PARAM_BOOL
				);
			}
			if ($operand instanceof FlagExpression) {
				return $this->flagExpressionToQuery($operand, $qb, $tableAlias);
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
	 * @param Mailbox $mailbox
	 * @param string $userId
	 * @param int[] $ids
	 *
	 * @return Message[]
	 */
	public function findByMailboxAndIds(Mailbox $mailbox, string $userId, array $ids): array {
		if (empty($ids)) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mailbox_id', $qb->createNamedParameter($mailbox->getId()), IQueryBuilder::PARAM_INT),
				$qb->expr()->in('id', $qb->createParameter('ids'))
			)
			->orderBy('sent_at', 'desc');

		$results = [];
		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$results[] = $this->findRelatedData($this->findEntities($qb), $userId);
		}
		return array_merge([], ...$results);
	}

	/**
	 * @param string $userId
	 * @param int[] $ids
	 *
	 * @return Message[]
	 */
	public function findByIds(string $userId, array $ids): array {
		if (empty($ids)) {
			return [];
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('id', $qb->createParameter('ids'))
			)
			->orderBy('sent_at', 'desc');

		$results = [];
		foreach (array_chunk($ids, 1000) as $chunk) {
			$qb->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$results[] = $this->findRelatedData($this->findEntities($qb), $userId);
		}
		return array_merge([], ...$results);
	}

	/**
	 * @param Message[] $messages
	 *
	 * @return Message[]
	 */
	private function findRecipients(array $messages): array {
		/** @var Message[] $indexedMessages */
		$indexedMessages = array_combine(
			array_map(static function (Message $msg) {
				return $msg->getId();
			}, $messages),
			$messages
		);

		$qb2 = $this->db->getQueryBuilder();
		$qb2->select('label', 'email', 'type', 'message_id')
			->from('mail_recipients')
			->where($qb2->expr()->in('message_id', $qb2->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));

		$recipientsResults = [];
		foreach (array_chunk(array_keys($indexedMessages), 1000) as $chunk) {
			$qb2->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$result = $qb2->execute();
			$recipientsResults[] = $result->fetchAll();
			$result->closeCursor();
		}

		$recipientsResults = array_merge([], ...$recipientsResults);

		foreach ($recipientsResults as $recipient) {
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
					$message->setBcc(
						$message->getBcc()->merge(AddressList::fromRow($recipient))
					);
					break;
			}
		}

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
	 * @param array $ids
	 * @return int[]
	 */
	public function findNewIds(Mailbox $mailbox, array $ids): array {
		$select = $this->db->getQueryBuilder();
		$subSelect = $this->db->getQueryBuilder();

		$subSelect
			->select($subSelect->func()->min('sent_at'))
			->from($this->getTableName())
			->where(
				$subSelect->expr()->eq('mailbox_id', $select->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$subSelect->expr()->orX(
					$subSelect->expr()->in('id', $select->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY)
				)
			);

		$selfJoin = $select->expr()->andX(
			$select->expr()->eq('m.mailbox_id', 'm2.mailbox_id', IQueryBuilder::PARAM_INT),
			$select->expr()->eq('m.thread_root_id', 'm2.thread_root_id', IQueryBuilder::PARAM_INT),
			$select->expr()->lt('m.sent_at', 'm2.sent_at', IQueryBuilder::PARAM_INT)
		);

		$select
			->select('m.id')
			->from($this->getTableName(), 'm')
			->leftJoin('m', $this->getTableName(), 'm2', $selfJoin)
			->where(
				$select->expr()->eq('m.mailbox_id', $select->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
				$select->expr()->andX($subSelect->expr()->notIn('m.id', $select->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY)),
				$select->expr()->isNull('m2.id'),
				$select->expr()->gt('m.sent_at', $select->createFunction('(' . $subSelect->getSQL() . ')'), IQueryBuilder::PARAM_INT)
			)
			->orderBy('m.sent_at', 'desc');

		$results = [];
		foreach (array_chunk($ids, 1000) as $chunk) {
			$select->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$results[] = $this->findIds($select);
		}

		return array_merge([], ...$results);
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
		$ids = [];
		while ($row = $result->fetch()) {
			$ids[] = (int) $row['id'];
		}
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$query->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->execute();
		}
		$qb3 = $this->db->getQueryBuilder();
		$recipientIdsQuery = $qb3->selectDistinct('r.id')
			->from('mail_recipients', 'r')
			->leftJoin('r', 'mail_messages', 'm', $qb3->expr()->eq('r.message_id', 'm.id'))
			->where(
				$qb3->expr()->isNull('m.id'),
				$qb3->expr()->isNull('r.local_message_id')
			);
		$result = $recipientIdsQuery->execute();
		while ($row = $result->fetch()) {
			$ids[] = (int) $row['id'];
		}
		$result->closeCursor();

		$qb4 = $this->db->getQueryBuilder();
		$recipientsQuery = $qb4
			->delete('mail_recipients')
			->where($qb4->expr()->in('id', $qb4->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$recipientsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$recipientsQuery->execute();
		}
	}

	public function getIdForUid(Mailbox $mailbox, int $uid): ?int {
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
		$result->closeCursor();
		if (empty($rows)) {
			return null;
		}
		return (int)$rows[0]['id'];
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

	public function resetInReplyTo(): int {
		$qb = $this->db->getQueryBuilder();

		$update = $qb->update($this->tableName)
			->set('in_reply_to', $qb->createNamedParameter('NULL', IQueryBuilder::PARAM_NULL))
			->where(
				$qb->expr()->like('in_reply_to', $qb->createNamedParameter("<>", IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			);
		return $update->execute();
	}

	/**
	 * Get all iMIP messages from the last two weeks
	 * that haven't been processed yet
	 * @return Message[]
	 */
	public function findIMipMessagesAscending(): array {
		$time = $this->timeFactory->getTime() - 60 * 60 * 24 * 14;
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('imip_message', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->eq('imip_processed', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->eq('imip_error', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->eq('flag_junk', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->gt('sent_at', $qb->createNamedParameter($time, IQueryBuilder::PARAM_INT)),
			)->orderBy('sent_at', 'ASC'); // make sure we don't process newer messages first

		return $this->findEntities($select);
	}

	/**
	 * @return Message[]
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function getUnanalyzed(int $lastRun, array $mailboxIds): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->lte('sent_at', $qb->createNamedParameter($lastRun, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
				$qb->expr()->eq('structure_analyzed', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL), IQueryBuilder::PARAM_BOOL),
				$qb->expr()->in('mailbox_id', $qb->createNamedParameter($mailboxIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY),
			)->orderBy('sent_at', 'ASC');

		return $this->findEntities($select);
	}
}
