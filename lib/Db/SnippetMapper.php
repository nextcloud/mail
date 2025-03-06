<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Snippet>
 */
class SnippetMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_snippets');
	}

	/**
	 * @param int $id
	 * @param string $owner
	 * @return Snippet|null
	 */
	public function find(int $id, string $owner): ?Snippet {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	/**
	 * @param string $owner
	 * @return Snippet[]
	 */
	public function findAll(string $owner): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param array $groups
	 * @return Snippet[]
	 */
	public function findSharedWithMe(string $userId, array $groups): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('s.*')
			->from($this->getTableName(), 's')
			->join('s', 'mail_snippets_shares', 'share', $qb->expr()->eq('s.id', 'share.snippet_id'))
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
