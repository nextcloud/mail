<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<SnippetShare>
 */
class SnippetShareMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_snippets_shares');
	}

	/**
	 * @param int $id
	 * @param string $owner
	 * @return Snippet
	 *
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $owner): Snippet {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('owner', $qb->createNamedParameter($owner)));

		return $this->findEntity($qb);
	}

	/**
	 * @param string $owner
	 * @return SnippetShare[]
	 */
	public function findAllShares(string $owner): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('sshare.*')
			->from($this->getTableName(), 'sshare')
			->join('sshare', 'mail_snippets', 's', $qb->expr()->eq('sshare.snippet_id', 's.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('s.owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $owner
	 * @param string $snippetId
	 *
	 * @return SnippetShare[]
	 */
	public function findSnippetShares(string $owner, string $snippetId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('sshare.*')
			->from($this->getTableName(), 'sshare')
			->where(
				$qb->expr()->eq('s.owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('sshare.snippet_id', $qb->createNamedParameter($snippetId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}


}
