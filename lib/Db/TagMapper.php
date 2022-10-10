<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
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

use function array_map;
use function array_chunk;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

/**
 * @template-extends QBMapper<Tag>
 */
class TagMapper extends QBMapper {
	/** @var IL10N */
	private $l10n;

	public function __construct(IDBConnection $db,
								IL10N $l10n) {
		parent::__construct($db, 'mail_tags');
		$this->l10n = $l10n;
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getTagByImapLabel(string $imapLabel, string $userId): Tag {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('imap_label', $qb->createNamedParameter($imapLabel)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);
		return $this->findEntity($qb);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getTagForUser(int $id, string $userId): Tag {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);
		return $this->findEntity($qb);
	}

	/**
	 * @return Tag[]
	 */
	public function getAllTagsForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);
		return $this->findEntities($qb);
	}

	/**
	 * Tag a message in the DB
	 *
	 * To tag (flag) a message on IMAP, @see \OCA\Mail\Service\MailManager::tagMessage
	 */
	public function tagMessage(Tag $tag, string $messageId, string $userId): void {
		try {
			$tag = $this->getTagByImapLabel($tag->getImapLabel(), $userId);
		} catch (DoesNotExistException $e) {
			$tag = $this->insert($tag);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_message_tags')
		   ->setValue('imap_message_id', $qb->createNamedParameter($messageId))
		   ->setValue('tag_id', $qb->createNamedParameter($tag->getId(), IQueryBuilder::PARAM_INT));
		$qb->execute();
	}

	/**
	 * Remove a tag from a DB message
	 *
	 * This does not(!) untag a message on IMAP
	 */
	public function untagMessage(Tag $tag, string $messageId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('mail_message_tags')
			->where($qb->expr()->eq('imap_message_id', $qb->createNamedParameter($messageId)))
			->andWhere($qb->expr()->eq('tag_id', $qb->createNamedParameter($tag->getId())));
		$qb->execute();
	}

	/**
	 * @param Message[] $messages
	 * @param string $userId
	 * @return Tag[][]
	 */
	public function getAllTagsForMessages(array $messages, string $userId): array {
		$ids = array_map(function (Message $message) {
			return $message->getMessageId();
		}, $messages);

		$tags = [];
		$qb = $this->db->getQueryBuilder();
		$tagsQuery = $qb->selectDistinct(['t.*', 'mt.imap_message_id'])
			->from($this->getTableName(), 't')
			->join('t', 'mail_message_tags', 'mt', $qb->expr()->eq('t.id', 'mt.tag_id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->in('mt.imap_message_id', $qb->createParameter('ids'), IQueryBuilder::PARAM_STR_ARRAY),
				$qb->expr()->eq('t.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);

		foreach (array_chunk($ids, 1000) as $chunk) {
			$tagsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$queryResult = $tagsQuery->execute();

			while (($row = $queryResult->fetch()) !== false) {
				$messageId = $row['imap_message_id'];
				if (!isset($tags[$messageId])) {
					$tags[$messageId] = [];
				}

				// Construct a Tag instance but omit any other joined columns
				$tags[$messageId][] = Tag::fromRow(array_filter(
					$row,
					function (string $key) {
						return $key !== 'imap_message_id';
					},
					ARRAY_FILTER_USE_KEY
				));
			}
			$queryResult->closeCursor();
		}
		return $tags;
	}

	/**
	 * @param Message[] $messages
	 * @param string $userId
	 * @param string $imapLabel
	 * @return string[]
	 */
	public function getTaggedMessageIdsForMessages(array $messages, string $userId, string $imapLabel): array {
		$ids = array_map(static function (Message $message) {
			return $message->getMessageId();
		}, $messages);

		$qb = $this->db->getQueryBuilder();
		$tagsQuery = $qb->selectDistinct(['t.*', 'mt.imap_message_id'])
			->from($this->getTableName(), 't')
			->join('t', 'mail_message_tags', 'mt', $qb->expr()->eq('t.id', 'mt.tag_id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->in('mt.imap_message_id', $qb->createParameter('ids'), IQueryBuilder::PARAM_STR_ARRAY),
				$qb->expr()->eq('t.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('t.imap_label', $qb->createNamedParameter($imapLabel, IQueryBuilder::PARAM_STR))
			);

		$messageIds = [];
		foreach (array_chunk($ids, 1000) as $chunk) {
			$tagsQuery->setParameter('ids', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$queryResult = $tagsQuery->execute();

			while (($row = $queryResult->fetch()) !== false) {
				$messageIds[] = $row['imap_message_id'];
			}
			$queryResult->closeCursor();
		}
		return $messageIds;
	}

	/**
	 * Create some default system tags
	 *
	 * This is designed to be similar to Thunderbird's email tags
	 * $label1 to $label5 with the according states and colours
	 *
	 * <i>The array_udiff can be removed and the insert warpped in
	 * an exception as soon as NC20 is not supported any more</i>
	 *
	 * @link https://github.com/nextcloud/mail/issues/25
	 */
	public function createDefaultTags(MailAccount $account): void {
		$tags = [];
		for ($i = 1; $i < 6; $i++) {
			$tag = new Tag();
			$tag->setImapLabel('$label' . $i);
			$tag->setUserId($account->getUserId());
			switch ($i) {
				case 1:
					$tag->setDisplayName($this->l10n->t('Important'));
					$tag->setColor('#FF7A66');
					$tag->setIsDefaultTag(true);
					break;
				case 2:
					$tag->setDisplayName($this->l10n->t('Work'));
					$tag->setColor('#31CC7C');
					$tag->setIsDefaultTag(true);
					break;
				case 3:
					$tag->setDisplayName($this->l10n->t('Personal'));
					$tag->setColor('#A85BF7');
					$tag->setIsDefaultTag(true);
					break;
				case 4:
					$tag->setDisplayName($this->l10n->t('To Do'));
					$tag->setColor('#317CCC');
					$tag->setIsDefaultTag(true);
					break;
				case 5:
					$tag->setDisplayName($this->l10n->t('Later'));
					$tag->setColor('#B4A443');
					$tag->setIsDefaultTag(true);
					break;
			}
			$tags[] = $tag;
		}
		$dbTags = $this->getAllTagsForUser($account->getUserId());
		$toInsert = array_udiff($tags, $dbTags, function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toInsert as $entity) {
			$this->insert($entity);
		}
	}

	public function deleteDuplicates(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->select('mt2.id')
		->from('mail_message_tags', 'mt2')
		->join('mt2', 'mail_message_tags', 'mt1', $qb->expr()->andX(
			$qb->expr()->gt('mt1.id', 'mt2.id'),
			$qb->expr()->eq('mt1.imap_message_id', 'mt2.imap_message_id'),
			$qb->expr()->eq('mt1.tag_id', 'mt2.tag_id')
		)
		);
		$result = $qb->execute();
		$rows = $result->fetchAll();
		$result->closeCursor();
		$ids = array_unique(array_map(function ($row) {
			return $row['id'];
		}, $rows));

		$deleteQB = $this->db->getQueryBuilder();
		$deleteQB->delete('mail_message_tags')
			->where($deleteQB->expr()->in('id', $deleteQB->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$deleteQB->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteQB->execute();
		}
	}

	public function deleteOrphans(): void {
		$qb = $this->db->getQueryBuilder();

		$qb->select('mt.id')
		->from('mail_message_tags', 'mt')
		->leftJoin('mt', $this->getTableName(), 't', $qb->expr()->eq('t.id', 'mt.tag_id'))
		->where($qb->expr()->isNull('t.id'));
		$result = $qb->execute();
		$rows = $result->fetchAll();
		$result->closeCursor();

		$ids = array_map(function (array $row) {
			return $row['id'];
		}, $rows);

		$deleteQB = $this->db->getQueryBuilder();
		$deleteQB->delete('mail_message_tags')
			->where($deleteQB->expr()->in('id', $deleteQB->createParameter('ids'), IQueryBuilder::PARAM_INT_ARRAY));
		foreach (array_chunk($ids, 1000) as $chunk) {
			$deleteQB->setParameter('ids', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deleteQB->execute();
		}
	}
}
