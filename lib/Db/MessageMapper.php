<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\Service\Search\Flag;
use OCA\Mail\Service\Search\FlagExpression;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Support\PerformanceLogger;
use OCA\Mail\Support\PerformanceLoggerTask;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\TTransactional;
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

	use TTransactional;

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
		$result = $query->executeQuery();
		$uids = array_map(static function (array $row) {
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
		$result = $query->executeQuery();
		$uids = array_map(static function (array $row) {
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

		$result = $query->executeQuery();
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
		if ($results === []) {
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
		if ($ids === []) {
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
			->andWhere(
				$messagesQuery->expr()->isNotNull('message_id'),
				$messagesQuery->expr()->orX(
					$messagesQuery->expr()->isNotNull('in_reply_to'),
					$messagesQuery->expr()->neq('references', $messagesQuery->createNamedParameter('[]'))
				),
			);

		$result = $messagesQuery->executeQuery();
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

				$query->executeStatement();
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
			$qb1->setValue('imip_message', $qb1->createParameter('imip_message'));

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
				$qb1->setParameter('imip_message', $message->isImipMessage(), IQueryBuilder::PARAM_BOOL);
				$qb1->executeStatement();

				$message->setId($qb1->getLastInsertId());
				$recipientTypes = [
					Address::TYPE_FROM => $message->getFrom(),
					Address::TYPE_TO => $message->getTo(),
					Address::TYPE_CC => $message->getCc(),
					Address::TYPE_BCC => $message->getBcc(),
				];
				foreach ($recipientTypes as $type => $recipients) {
					foreach ($recipients->iterate() as $recipient) {
						if ($recipient->getEmail() === null) {
							// If for some reason the e-mail is not set we should ignore this entry
							continue;
						}

						$qb2->setParameter('message_id', $message->getId(), IQueryBuilder::PARAM_INT);
						$qb2->setParameter('type', $type, IQueryBuilder::PARAM_INT);
						$qb2->setParameter('label', mb_strcut($recipient->getLabel(), 0, 255), IQueryBuilder::PARAM_STR);
						$qb2->setParameter('email', mb_strcut($recipient->getEmail(), 0, 255), IQueryBuilder::PARAM_STR);

						$qb2->executeStatement();
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

		// MailboxId is the same for all messages according to updateBulk() call
		$mailboxId = $messages[0]->getMailboxId();

		$flags = [
			'flag_answered',
			'flag_deleted',
			'flag_draft',
			'flag_flagged',
			'flag_seen',
			'flag_forwarded',
			'flag_junk',
			'flag_notjunk',
			'flag_mdnsent',
			'flag_important',
		];

		$updateData = [];
		foreach ($flags as $flag) {
			$updateData[$flag.'_true'] = [];
			$updateData[$flag.'_false'] = [];
		}

		foreach ($messages as $message) {
			if (empty($message->getUpdatedFields()) === false) {
				if ($message->getFlagAnswered()) {
					$updateData['flag_answered_true'][] = $message->getUid();
				} else {
					$updateData['flag_answered_false'][] = $message->getUid();
				}

				if ($message->getFlagDeleted()) {
					$updateData['flag_deleted_true'][] = $message->getUid();
				} else {
					$updateData['flag_deleted_false'][] = $message->getUid();
				}

				if ($message->getFlagDraft()) {
					$updateData['flag_draft_true'][] = $message->getUid();
				} else {
					$updateData['flag_draft_false'][] = $message->getUid();
				}

				if ($message->getFlagFlagged()) {
					$updateData['flag_flagged_true'][] = $message->getUid();
				} else {
					$updateData['flag_flagged_false'][] = $message->getUid();
				}

				if ($message->getFlagSeen()) {
					$updateData['flag_seen_true'][] = $message->getUid();
				} else {
					$updateData['flag_seen_false'][] = $message->getUid();
				}

				if ($message->getFlagForwarded()) {
					$updateData['flag_forwarded_true'][] = $message->getUid();
				} else {
					$updateData['flag_forwarded_false'][] = $message->getUid();
				}

				if ($message->getFlagJunk()) {
					$updateData['flag_junk_true'][] = $message->getUid();
				} else {
					$updateData['flag_junk_false'][] = $message->getUid();
				}

				if ($message->getFlagNotjunk()) {
					$updateData['flag_notjunk_true'][] = $message->getUid();
				} else {
					$updateData['flag_notjunk_false'][] = $message->getUid();
				}

				if ($message->getFlagMdnsent()) {
					$updateData['flag_mdnsent_true'][] = $message->getUid();
				} else {
					$updateData['flag_mdnsent_false'][] = $message->getUid();
				}

				if ($message->getFlagImportant()) {
					$updateData['flag_important_true'][] = $message->getUid();
				} else {
					$updateData['flag_important_false'][] = $message->getUid();
				}
			}
		}


		try {
			// UPDATE messages SET flag true/false WHERE uid in (uids) -> for each flag
			// => total of 20 queries
			foreach ($flags as $flag) {
				$queryTrue = $this->db->getQueryBuilder();
				$queryTrue->update($this->getTableName())
					->set($flag, $queryTrue->createNamedParameter(1, IQueryBuilder::PARAM_INT))
					->set('updated_at', $queryTrue->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
					->where($queryTrue->expr()->andX(
						$queryTrue->expr()->in('uid', $queryTrue->createParameter('uids')),
						$queryTrue->expr()->eq('mailbox_id', $queryTrue->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT)),
						$queryTrue->expr()->eq($flag, $queryTrue->createNamedParameter(0, IQueryBuilder::PARAM_INT))
					));
				foreach (array_chunk($updateData[$flag.'_true'], 1000) as $chunk) {
					$queryTrue->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
					$queryTrue->executeStatement();
				}

				$queryFalse = $this->db->getQueryBuilder();
				$queryFalse->update($this->getTableName())
					->set($flag, $queryFalse->createNamedParameter(0, IQueryBuilder::PARAM_INT))
					->set('updated_at', $queryFalse->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
					->where($queryFalse->expr()->andX(
						$queryFalse->expr()->in('uid', $queryFalse->createParameter('uids')),
						$queryFalse->expr()->eq('mailbox_id', $queryFalse->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT)),
						$queryFalse->expr()->eq($flag, $queryFalse->createNamedParameter(1, IQueryBuilder::PARAM_INT))
					));
				foreach (array_chunk($updateData[$flag.'_false'], 1000) as $chunk) {
					$queryFalse->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
					$queryFalse->executeStatement();
				}

				$perf->step('Set ' . $flag . ' in messages.');
			}

			// get all tags before the loop and create a mapping [message_id => [tag,...]] but only if permflags are enabled
			$tags = [];
			if ($permflagsEnabled) {
				$tags = $this->tagMapper->getAllTagsForMessages($messages, $account->getUserId());
				$perf->step('Selected Tags for all messages');
			}

			foreach ($messages as $message) {
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

		if ($imapTags === [] && $dbTags === []) {
			// neither old nor new tags
			return;
		}

		$toAdd = array_udiff($imapTags, $dbTags, static function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toAdd as $tag) {
			$this->tagMapper->tagMessage($tag, $message->getMessageId(), $account->getUserId());
		}
		$perf->step('Tagged messages');

		if ($dbTags === []) {
			// we have nothing to possibly remove
			return;
		}

		$toRemove = array_udiff($dbTags, $imapTags, static function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toRemove as $tag) {
			$this->tagMapper->untagMessage($tag, $message->getMessageId());
		}
		$perf->step('Untagged messages');
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
				->set('encrypted', $query->createParameter('encrypted'))
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
					$convertedText = mb_convert_encoding($message->getPreviewText(), 'UTF-8', 'UTF-8');
					//converting the spaces is necessary for ltrim to work
					$previewText = mb_strcut(ltrim(preg_replace('/\s/u', ' ', $convertedText)), 0, 255);

					// Make sure modifications are visible when these objects are used right away
					$message->setPreviewText($previewText);
				}
				$query->setParameter(
					'preview_text',
					$previewText,
					$previewText === null ? IQueryBuilder::PARAM_NULL : IQueryBuilder::PARAM_STR
				);
				$query->setParameter('imip_message', $message->isImipMessage(), IQueryBuilder::PARAM_BOOL);
				$query->setParameter('encrypted', $message->isEncrypted(), IQueryBuilder::PARAM_BOOL);

				$query->executeStatement();
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
				$query->executeStatement();
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

		$cursor = $messageIdQuery->executeQuery();
		$messageIds = $cursor->fetchAll();
		$cursor->closeCursor();

		$messageIds = array_map(static function (array $row) {
			return (int)$row['id'];
		}, $messageIds);

		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery->delete('mail_recipients')
			->where($deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createParameter('ids')));

		foreach (array_chunk($messageIds, 1000) as $chunk) {
			// delete all related recipient entries
			$deleteRecipientsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteRecipientsQuery->executeStatement();
		}

		$query = $this->db->getQueryBuilder();

		$query->delete($this->getTableName())
			->where($query->expr()->eq('mailbox_id', $query->createNamedParameter($mailbox->getId())));

		$query->executeStatement();
	}

	public function deleteByUid(Mailbox $mailbox, int ...$uids): void {
		$selectMessageIdsQuery = $this->db->getQueryBuilder();
		$deleteRecipientsQuery = $this->db->getQueryBuilder();
		$deleteMessagesQuery = $this->db->getQueryBuilder();

		$selectMessageIdsQuery->select('id')
			->from($this->getTableName())
			->where(
				$selectMessageIdsQuery->expr()->eq('mailbox_id', $selectMessageIdsQuery->createNamedParameter($mailbox->getId())),
				$selectMessageIdsQuery->expr()->in('uid', $deleteMessagesQuery->createParameter('uids')),
			);
		$deleteRecipientsQuery->delete('mail_recipients')
			->where(
				$deleteRecipientsQuery->expr()->in('message_id', $deleteRecipientsQuery->createParameter('ids')),
			);
		$deleteMessagesQuery->delete('mail_messages')
			->where(
				$deleteMessagesQuery->expr()->in('id', $deleteMessagesQuery->createParameter('ids')),
			);

		foreach (array_chunk($uids, 1000) as $chunk) {
			$this->atomic(function () use ($selectMessageIdsQuery, $deleteRecipientsQuery, $deleteMessagesQuery, $chunk) {
				$selectMessageIdsQuery->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				$selectResult = $selectMessageIdsQuery->executeQuery();
				$ids = array_map('intval', $selectResult->fetchAll(\PDO::FETCH_COLUMN));
				$selectResult->closeCursor();
				if (empty($ids)) {
					// Avoid useless queries
					return;
				}

				// delete all related recipient entries
				$deleteRecipientsQuery->setParameter('ids', $ids, IQueryBuilder::PARAM_INT_ARRAY);
				$deleteRecipientsQuery->executeStatement();

				// delete all messages
				$deleteMessagesQuery->setParameter('ids', $ids, IQueryBuilder::PARAM_INT_ARRAY);
				$deleteMessagesQuery->executeStatement();
			}, $this->db);
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
	public function findIdsByQuery(Mailbox $mailbox, SearchQuery $query, string $sortOrder, ?int $limit, ?array $uids = null): array {
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

		$textOrs = [];

		if (!empty($query->getFrom())) {
			if ($query->getMatch() === 'anyof') {
				$textOrs[] = $qb->expr()->andX(
					$qb->expr()->orX(
						...array_map(function (string $email) use ($qb) {
							return $qb->expr()->iLike('r0.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getFrom()),
						...array_map(function (string $label) use ($qb) {
							return $qb->expr()->iLike('r0.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getFrom()),
					),
					$qb->expr()->eq('r0.type', $qb->createNamedParameter(Recipient::TYPE_FROM, IQueryBuilder::PARAM_INT)),
				);
			} else {
				$select->andWhere(
					$qb->expr()->orX(
						...array_map(function (string $email) use ($qb) {
							return $qb->expr()->iLike('r0.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getFrom()),
						...array_map(function (string $label) use ($qb) {
							return $qb->expr()->iLike('r0.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getFrom()),
					),
					$qb->expr()->eq('r0.type', $qb->createNamedParameter(Recipient::TYPE_FROM, IQueryBuilder::PARAM_INT)),
				);

			}

		}
		if (!empty($query->getTo())) {
			if ($query->getMatch() === 'anyof') {
				$textOrs[] = $qb->expr()->andX(
					$qb->expr()->orX(
						...array_map(function (string $email) use ($qb) {
							return $qb->expr()->iLike('r1.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getTo()),
						...array_map(function (string $label) use ($qb) {
							return $qb->expr()->iLike('r1.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getTo()),
					),
					$qb->expr()->eq('r1.type', $qb->createNamedParameter(Recipient::TYPE_TO, IQueryBuilder::PARAM_INT)),
				);
			} else {

				$select->andWhere(
					$qb->expr()->orX(
						...array_map(function (string $email) use ($qb) {
							return $qb->expr()->iLike('r1.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getTo()),
						...array_map(function (string $label) use ($qb) {
							return $qb->expr()->iLike('r1.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
						}, $query->getTo()),
					),
					$qb->expr()->eq('r1.type', $qb->createNamedParameter(Recipient::TYPE_TO, IQueryBuilder::PARAM_INT)),
				);
			}

		}
		if (!empty($query->getCc())) {
			$select->andWhere(
				$qb->expr()->orX(
					...array_map(function (string $email) use ($qb) {
						return $qb->expr()->iLike('r2.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
					}, $query->getCc()),
					...array_map(function (string $label) use ($qb) {
						return $qb->expr()->iLike('r2.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
					}, $query->getCc()),
				),
				$qb->expr()->eq('r2.type', $qb->createNamedParameter(Recipient::TYPE_CC, IQueryBuilder::PARAM_INT)),
			);
		}
		if (!empty($query->getBcc())) {
			$select->andWhere(
				$qb->expr()->orX(
					...array_map(function (string $email) use ($qb) {
						return $qb->expr()->iLike('r3.email', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($email) . '%', IQueryBuilder::PARAM_STR));
					}, $query->getBcc()),
					...array_map(function (string $label) use ($qb) {
						return $qb->expr()->iLike('r3.label', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($label) . '%', IQueryBuilder::PARAM_STR));
					}, $query->getBcc()),
				),
				$qb->expr()->eq('r3.type', $qb->createNamedParameter(Recipient::TYPE_BCC, IQueryBuilder::PARAM_INT)),
			);
		}

		if (!empty($query->getSubjects())) {
			$textOrs[] = $qb->expr()->orX(
				...array_map(function (string $subject) use ($qb) {
					return $qb->expr()->iLike(
						'm.subject',
						$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($subject) . '%', IQueryBuilder::PARAM_STR),
						IQueryBuilder::PARAM_STR
					);
				}, $query->getSubjects())
			);
		}
		// createParameter
		if ($uids !== null) {
			// In the case of body+subject search we need a combination of both results,
			// thus the orWhere in every other case andWhere should do the job.
			if(!empty($query->getSubjects())) {
				$textOrs[] = $qb->expr()->in('m.uid', $qb->createParameter('uids'));
			} else {
				$select->andWhere(
					$qb->expr()->in('m.uid', $qb->createParameter('uids'))
				);
			}
		}
		if (!empty($textOrs)) {
			$select->andWhere($qb->expr()->orX(...$textOrs));
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

		if ($query->getCursor() !== null && $sortOrder === IMailSearch::ORDER_NEWEST_FIRST) {
			$select->andWhere(
				$qb->expr()->lt('m.sent_at', $qb->createNamedParameter($query->getCursor(), IQueryBuilder::PARAM_INT))
			);
		} elseif ($query->getCursor() !== null && $sortOrder === IMailSearch::ORDER_OLDEST_FIRST) {
			$select->andWhere(
				$qb->expr()->gt('m.sent_at', $qb->createNamedParameter($query->getCursor(), IQueryBuilder::PARAM_INT))
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

		if ($sortOrder === 'ASC') {
			$select->orderBy('m.sent_at', $sortOrder);
		} else {
			$select->orderBy('m.sent_at', 'DESC');
		}

		if ($limit !== null) {
			$select->setMaxResults($limit);
		}

		if ($uids !== null) {
			return array_flat_map(function (array $chunk) use ($qb, $select) {
				$qb->setParameter('uids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				return array_map(static function (Message $message) {
					return $message->getId();
				}, $this->findEntities($select));
			}, array_chunk($uids, 1000));
		}

		$result = array_map(static function (Message $message) {
			return $message->getId();
		}, $this->findEntities($select));
		return $result;
	}

	public function findIdsGloballyByQuery(IUser $user, SearchQuery $query, ?int $limit, ?array $uids = null): array {
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
				return array_map(static function (Message $message) {
					return $message->getId();
				}, $this->findEntities($select));
			}, array_chunk($uids, 1000));
		}

		return array_map(static function (Message $message) {
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
				return (string)$qb->expr()->andX(...$operands);
			case 'or':
				/** @psalm-suppress InvalidCast */
				return (string)$qb->expr()->orX(...$operands);
			default:
				throw new RuntimeException('Unknown operator ' . $expr->getOperator());
		}
	}

	private function flagToColumnName(Flag $flag): string {
		// workaround for @link https://github.com/nextcloud/mail/issues/25
		if ($flag->getFlag() === Tag::LABEL_IMPORTANT) {
			return 'flag_important';
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
		if ($ids === []) {
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
	 * @param string $sortOrder
	 *
	 * @return Message[]
	 */
	public function findByIds(string $userId, array $ids, string $sortOrder): array {
		if ($ids === []) {
			return [];
		}
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('id', $qb->createParameter('ids'))
			)
			->orderBy('sent_at', $sortOrder);

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
			$result = $qb2->executeQuery();
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
		$messages = array_map(static function ($message) use ($tags) {
			$message->setTags($tags[$message->getMessageId()] ?? []);
			return $message;
		}, $messages);
		return $messages;
	}

	/**
	 * @param Mailbox $mailbox
	 * @param array $ids
	 * @param int|null $lastMessageTimestamp
	 * @param IMailSearch::ORDER_* $sortOrder
	 *
	 * @return int[]
	 */
	public function findNewIds(Mailbox $mailbox, array $ids, ?int $lastMessageTimestamp, string $sortOrder): array {
		$select = $this->db->getQueryBuilder();
		$subSelect = $this->db->getQueryBuilder();

		$subSelect
			->select($sortOrder === IMailSearch::ORDER_NEWEST_FIRST ?
				$subSelect->func()->min('sent_at') :
				$subSelect->func()->max('sent_at'))
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
			$sortOrder === IMailSearch::ORDER_NEWEST_FIRST ?
				$select->expr()->lt('m.sent_at', 'm2.sent_at', IQueryBuilder::PARAM_INT) :
				$select->expr()->gt('m.sent_at', 'm2.sent_at', IQueryBuilder::PARAM_INT)
		);
		$wheres = [$select->expr()->eq('m.mailbox_id', $select->createNamedParameter($mailbox->getId(), IQueryBuilder::PARAM_INT)),
			$select->expr()->andX($subSelect->expr()->notIn('m.id', $select->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY)),
			$select->expr()->isNull('m2.id'),
		];
		if ($sortOrder === IMailSearch::ORDER_NEWEST_FIRST) {
			$wheres[] = $select->expr()->gt('m.sent_at', $select->createFunction('(' . $subSelect->getSQL() . ')'), IQueryBuilder::PARAM_INT);
		} else {
			$wheres[] = $select->expr()->lt('m.sent_at', $select->createFunction('(' . $subSelect->getSQL() . ')'), IQueryBuilder::PARAM_INT);
		}

		if ($lastMessageTimestamp !== null && $sortOrder === IMailSearch::ORDER_OLDEST_FIRST) {
			// Don't consider old "new messages" as new when their UID has already been seen before
			$wheres[] = $select->expr()->lt('m.sent_at', $select->createNamedParameter($lastMessageTimestamp, IQueryBuilder::PARAM_INT));
		}

		$select
			->select(['m.id', 'm.sent_at'])
			->from($this->getTableName(), 'm')
			->leftJoin('m', $this->getTableName(), 'm2', $selfJoin)
			->where(...$wheres)
			->orderBy('m.sent_at', $sortOrder === IMailSearch::ORDER_NEWEST_FIRST ? 'desc' : 'asc');

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
		$result = $idsQuery->executeQuery();
		$ids = [];
		while ($row = $result->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$result->closeCursor();

		$qb2 = $this->db->getQueryBuilder();
		$query = $qb2
			->delete($this->getTableName())
			->where($qb2->expr()->in('id', $qb2->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$query->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->executeStatement();
		}
		$qb3 = $this->db->getQueryBuilder();
		$recipientIdsQuery = $qb3->selectDistinct('r.id')
			->from('mail_recipients', 'r')
			->leftJoin('r', 'mail_messages', 'm', $qb3->expr()->eq('r.message_id', 'm.id'))
			->where(
				$qb3->expr()->isNull('m.id'),
				$qb3->expr()->isNull('r.local_message_id')
			);
		$result = $recipientIdsQuery->executeQuery();
		while ($row = $result->fetch()) {
			$ids[] = (int)$row['id'];
		}
		$result->closeCursor();

		$qb4 = $this->db->getQueryBuilder();
		$recipientsQuery = $qb4
			->delete('mail_recipients')
			->where($qb4->expr()->in('id', $qb4->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$recipientsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$recipientsQuery->executeStatement();
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
		$result = $select->executeQuery();
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
				$qb->expr()->like('in_reply_to', $qb->createNamedParameter('<>', IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR)
			);
		return $update->executeStatement();
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

	/**
	 * @param int $mailboxId
	 * @param int $before UNIX timestamp (seconds)
	 *
	 * @return Message[]
	 */
	public function findMessagesKnownSinceBefore(int $mailboxId, int $before): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('m.*')
			->from($this->getTableName(), 'm')
			->join('m', 'mail_messages_retention', 'mr', $qb->expr()->andX(
				$qb->expr()->eq(
					'm.mailbox_id',
					'mr.mailbox_id',
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->eq(
					'm.uid',
					'mr.uid',
					IQueryBuilder::PARAM_INT,
				),
			))
			->where(
				$qb->expr()->eq(
					'm.mailbox_id',
					$qb->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->lt(
					'mr.known_since',
					$qb->createNamedParameter($before, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
			);

		return $this->findEntities($select);
	}

	/**
	 * Finds snoozed messages that are ready to wake since $time
	 *
	 * @param int $mailboxId
	 * @param int $time UNIX timestamp (seconds)
	 *
	 * @return Message[]
	 */
	public function findMessagesToUnSnooze(int $mailboxId, int $time): array {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('m.*')
			->from($this->getTableName(), 'm')
			->join('m', 'mail_messages_snoozed', 'mr', $qb->expr()->andX(
				$qb->expr()->eq(
					'm.mailbox_id',
					'mr.mailbox_id',
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->eq(
					'm.uid',
					'mr.uid',
					IQueryBuilder::PARAM_INT,
				),
			))
			->where(
				$qb->expr()->eq(
					'm.mailbox_id',
					$qb->createNamedParameter($mailboxId, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
				$qb->expr()->lt(
					'mr.snoozed_until',
					$qb->createNamedParameter($time, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT,
				),
			);

		return $this->findEntities($select);
	}

	/**
	 * Delete all duplicated cached messages.
	 * Some messages (with the same mailbox_id and uid) where inserted twice and this method cleans
	 * up the duplicated rows.
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function deleteDuplicateUids(): void {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('t1.id', 't1.mailbox_id', 't1.uid')
			->from($this->getTableName(), 't1')
			->innerJoin('t1', $this->getTableName(), 't2', $qb->expr()->andX(
				$qb->expr()->eq('t1.mailbox_id', 't2.mailbox_id', IQueryBuilder::PARAM_INT),
				$qb->expr()->eq('t1.uid', 't2.uid', IQueryBuilder::PARAM_INT),
				$qb->expr()->neq('t1.id', 't2.id', IQueryBuilder::PARAM_INT),
			))
			->executeQuery();

		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete($this->getTableName())
			->where(
				$deleteQb->expr()->neq(
					'id',
					$deleteQb->createParameter('id'),
					IQueryBuilder::PARAM_INT,
				),
				$deleteQb->expr()->eq(
					'mailbox_id',
					$deleteQb->createParameter('mailbox_id'),
					IQueryBuilder::PARAM_INT,
				),
				$deleteQb->expr()->eq(
					'uid',
					$deleteQb->createParameter('uid'),
					IQueryBuilder::PARAM_INT,
				),
			);

		$handledMailboxIdUidPairs = [];
		while ($row = $result->fetch()) {
			$pair = $row['mailbox_id'] . ':' . $row['uid'];
			if (isset($handledMailboxIdUidPairs[$pair])) {
				continue;
			}

			$deleteQb->setParameter('id', $row['id'], IQueryBuilder::PARAM_INT);
			$deleteQb->setParameter('mailbox_id', $row['mailbox_id'], IQueryBuilder::PARAM_INT);
			$deleteQb->setParameter('uid', $row['uid'], IQueryBuilder::PARAM_INT);
			$deleteQb->executeStatement();

			$handledMailboxIdUidPairs[$pair] = true;
		}

		$result->closeCursor();
	}
}
