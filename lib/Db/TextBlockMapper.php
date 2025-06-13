<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<TextBlock>
 */
class TextBlockMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_text_blocks');
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $owner): TextBlock {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		return $this->findEntity($qb);
	}
	/**
	 * @param string $owner Text blocks' owner
	 * @return TextBlock[]
	 */
	public function findAll(string $owner) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}
	/**
	 * @param string $userId User ID of the user who is looking for shared text blocks
	 * @param array $groups Array of group IDs the user belongs to
	 * @return TextBlock[]
	 */
	public function findSharedWithMe(string $userId, array $groups) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('s.*')
			->from($this->getTableName(), 's')
			->join('s', 'mail_blocks_shares', 'share', $qb->expr()->eq('s.id', 'share.text_block_id'))
			->where($qb->expr()->andX(
				$qb->expr()->eq('share.share_with', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('share.type', $qb->createNamedParameter('user', IQueryBuilder::PARAM_STR))
			))
			->orWhere(
				$qb->expr()->andX(
					$qb->expr()->in('share.share_with', $qb->createNamedParameter($groups, IQueryBuilder::PARAM_STR_ARRAY)),
					$qb->expr()->eq('share.type', $qb->createNamedParameter('group', IQueryBuilder::PARAM_STR))
				)
			);
		return $this->findEntities($qb);
	}

}
