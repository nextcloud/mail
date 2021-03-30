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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\Exception;
use Throwable;
use function array_map;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
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
	public function getTagByImapLabel(string $imapLabel, string $userId): Entity {
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
	public function getTagForUser(int $id, string $userId): Entity {
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
	 * @throws DoesNotExistException
	 */
	public function getAllTagForUser(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
				);
		return $this->findEntities($qb);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function getTag(int $id): Entity {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Tag a message in the DB
	 *
	 * To tag (flag) a message on IMAP, @see \OCA\Mail\Service\MailManager::tagMessage
	 */
	public function tagMessage(Tag $tag, string $messageId, string $userId): void {
		/** @var Tag $exists */
		try {
			$exists = $this->getTagByImapLabel($tag->getImapLabel(), $userId);
			$tag->setId($exists->getId());
		} catch (DoesNotExistException $e) {
			$tag = $this->insert($tag);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_message_tags')
		   ->setValue('imap_message_id', $qb->createNamedParameter($messageId))
		   ->setValue('tag_id', $qb->createNamedParameter($tag->getId(), IQueryBuilder::PARAM_INT));
		try {
			$qb->execute();
		} catch (Throwable $e) {
			/**
			 * @psalm-suppress all
			 */
			if (class_exists(Exception::class) && ($e instanceof Exception) && $e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				// OK -> ignore
				return;
			}
			/**
			 * @psalm-suppress all
			 */
			if (class_exists(UniqueConstraintViolationException::class) && ($e instanceof UniqueConstraintViolationException)) {
				// OK -> ignore
				return;
			}

			throw $e;
		}
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
	 * @return Tag[][]
	 */
	public function getAllTagsForMessages(array $messages): array {
		$ids = array_map(function (Message $message) {
			return $message->getMessageId();
		}, $messages);

		$qb = $this->db->getQueryBuilder();
		$tagsQuery = $qb->select('t.*', 'mt.imap_message_id')
			->from($this->getTableName(), 't')
			->join('t', 'mail_message_tags', 'mt', $qb->expr()->eq('t.id', 'mt.tag_id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->in('mt.imap_message_id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY))
			);
		$queryResult = $tagsQuery->execute();
		$tags = [];
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
		return $tags;
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
						$tag->setColor('#FF0000');
						$tag->setIsDefaultTag(true);
						break;
					case 2:
						$tag->setDisplayName($this->l10n->t('Work'));
						$tag->setColor('#FFC300');
						$tag->setIsDefaultTag(true);
						break;
					case 3:
						$tag->setDisplayName($this->l10n->t('Personal'));
						$tag->setColor('#008000');
						$tag->setIsDefaultTag(true);
						break;
					case 4:
						$tag->setDisplayName($this->l10n->t('To Do'));
						$tag->setColor('#000080');
						$tag->setIsDefaultTag(true);
						break;
					case 5:
						$tag->setDisplayName($this->l10n->t('Later'));
						$tag->setColor('#800080');
						$tag->setIsDefaultTag(true);
						break;
			}
			$tags[] = $tag;
		}
		$dbTags = $this->getAllTagForUser($account->getUserId());
		$toInsert = array_udiff($tags, $dbTags, function (Tag $a, Tag $b) {
			return strcmp($a->getImapLabel(), $b->getImapLabel());
		});
		foreach ($toInsert as $entity) {
			$this->insert($entity);
		}
	}
}
