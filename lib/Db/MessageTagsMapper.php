<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<MessageTags>
 */
class MessageTagsMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_message_tags');
	}

	public function getMessagesByTag(int $id): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('tag_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),

			);
		return $this->findEntities($qb);
	}

}
