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
 * @template-extends QBMapper<TextBlockShare>
 */
class TextBlockShareMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_blocks_shares');
	}

	/**
	 * @param int $id
	 * @param string $owner
	 * @return TextBlockShare
	 *
	 * @throws DoesNotExistException
	 */
	public function find(int $textBlockId, string $shareWith): TextBlockShare {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('text_block_id', $qb->createNamedParameter($textBlockId)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($shareWith)));

		return $this->findEntity($qb);
	}

	public function shareExists(int $textBlockId, string $shareWith): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('text_block_id', $qb->createNamedParameter($textBlockId)))
			->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($shareWith)));

		try {
			$share = $this->findEntity($qb);
			if ($share !== null) {
				return true;
			}
		} catch (DoesNotExistException $e) {
			return false;
		}
		return false;
	}

	public function findAllShares(string $owner): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('sshare.*')
			->from($this->getTableName(), 'sshare')
			->join('sshare', 'mail_textBlocks', 's', $qb->expr()->eq('sshare.text_block_id', 's.id', IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('s.owner', $qb->createNamedParameter($owner, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param int $textBlockId
	 *
	 * @return TextBlockShare[]
	 */
	public function findTextBlockShares(int $textBlockId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('text_block_id', $qb->createNamedParameter($textBlockId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($qb);
	}

	public function deleteByTextBlockId(int $textBlockId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('text_block_id', $qb->createNamedParameter($textBlockId, IQueryBuilder::PARAM_INT))
			);
	}


}
