<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<MessageTemplate>
 */
class MessageTemplateMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_message_templates');
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function find(int $id): MessageTemplate {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws Exception
	 */
	public function findAll(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function saveTemplate(string $userId, string $title, string $body): MessageTemplate {
		$template = new MessageTemplate();
		$template->setUserId($userId);
		$template->setTitle($title);
		$template->setBody($body);

		return $this->insert($template);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function updateTemplate(int $id, $title, string $body): MessageTemplate {
		$messageTemplate = $this->find($id);
		$messageTemplate->setTitle($title);
		$messageTemplate->setBody($body);

		return $this->update($messageTemplate);
	}

	/**
	 * @throws DoesNotExistException
	 */
	public function deleteTemplate(int $id): MessageTemplate {
		$messageTemplate = $this->find($id);
		return $this->delete($messageTemplate);
	}

}
