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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Tag>
 */
class TagMapper extends QBMapper {

	/** @var LoggerInterface */
	private $logger;

	/** @var IL10N */
	private $l10n;

	public function __construct(IDBConnection $db,
								LoggerInterface $logger,
								IL10N $l10n) {
		parent::__construct($db, 'mail_tags');
		$this->logger = $logger;
		$this->l10n = $l10n;
	}

	/**
	 * @param string $imapLabel
	 * @return Entity
	 *
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
	 * @param integer $id
	 * @param string $userId
	 * @return Entity
	 *
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
	 * @param integer $id
	 * @param string $userId
	 * @return Entity
	 *
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
	 * To tag (flag) a message on IMAP, @see OCA\Mail\Service\MailManager::tagMessage
	 *
	 * @param Tag $tag
	 * @param string $messageId
	 * @param string $userId
	 * @return void
	 */
	public function tagMessage(Tag $tag, string $messageId, string $userId) {
		/** @var Tag $exists */
		try {
			$exists = $this->getTagByImapLabel($tag->getImapLabel(), $userId);
			$tag->setId($exists->getId());
		} catch (DoesNotExistException $e) {
			$tag = $this->insert($tag);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert('mail_message_tags');
		$qb->setValue('imap_message_id', $qb->createNamedParameter($messageId));
		$qb->setValue('tag_id', $qb->createNamedParameter($tag->getId(), IQueryBuilder::PARAM_INT));
		$qb->execute();
	}

	/**
	 * Remove a tag from a DB message
	 *
	 * @param Tag $tag
	 * @param string $messageId
	 * @return void
	 */
	public function untagMessage(Tag $tag, string $messageId) {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('mail_message_tags')
			->where($qb->expr()->eq('imap_message_id', $qb->createNamedParameter($messageId)))
			->where($qb->expr()->eq('tag_id', $qb->createNamedParameter($tag->getId())));
		$qb->execute();
	}

	/**
	 * @param array $messages
	 * @return array
	 */
	public function getAllTagsForMessages(array $messages):array {
		$ids = array_map(function (Message $message) {
			return $message->getMessageId();
		}, $messages);

		$qb = $this->db->getQueryBuilder();
		$ids = $qb->select('mt.*')
			->from('mail_message_tags', 'mt')
			->where(
				$qb->expr()->in('imap_message_id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY))
			);
		$qb = $qb->execute();
		$queryResult = $qb->fetchAll();
		if (empty($queryResult)) {
			return [];
		}
		$result = [];
		foreach ($queryResult as $qr) {
			$result[] = $qr['imap_message_id'];
			$result[$qr['imap_message_id']][] = $this->getTag((int)$qr['tag_id']);
		};
		return $result;
	}

	/**
	 * Create some default system tags
	 *
	 * This is designed to be similar to Thunderbird's email tags
	 * $label1 to $label5 with the according states and colours
	 *
	 * @param MailAccount $account
	 * @return void
	 *
	 * @link https://github.com/nextcloud/mail/issues/25
	 */
	public function createDefaultTags(MailAccount $account) {
		for ($i = 1; $i < 6; $i++) {
			$tag = new Tag();
			$tag->setImapLabel('$label' . $i);
			$tag->setUserId($account->getUserId());
			switch ($i) {
				case $i === 1:
					$tag->setDisplayName($this->l10n->t('Important'));
					$tag->setColor('#FF0000');
					$tag->setIsDefaultTag(true);

					break;
				case $i === 2:
					$tag->setDisplayName($this->l10n->t('Work'));
					$tag->setColor('#FFC300');
					$tag->setIsDefaultTag(true);

					break;
				case $i === 3:
					$tag->setDisplayName($this->l10n->t('Personal'));
					$tag->setColor('#008000');
					$tag->setIsDefaultTag(true);

					break;
				case $i === 4:
					$tag->setDisplayName($this->l10n->t('To Do'));
					$tag->setColor('#000080');
					$tag->setIsDefaultTag(true);

					break;
				case $i === 5:
					$tag->setDisplayName($this->l10n->t('Later'));
					$tag->setColor('#800080');
					$tag->setIsDefaultTag(true);
					break;
			}
			try {
				$this->insert($tag);
			} catch (\OCP\DB\Exception $e) {
				$this->logger->debug($e->getMessage(), ['exception' => $e]);
				continue;
			}
		}
	}
}
