<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Throwable;

/**
 * @template-extends QBMapper<ImipData>
 */
class ImipDataMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(
		IDBConnection $db,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct($db, 'mail_messages_imip');
	}

	public function findByMessageId(int $messageId): ?ImipData {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('imip_message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT))
			);

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * @throws Exception
	 */
	public function markAsImipMessage(int $messageId): void {
		if ($this->findByMessageId($messageId) !== null) {
			return;
		}

		$imipData = new ImipData();
		$imipData->setImipMessageId($messageId);
		$imipData->setError(false);
		$imipData->setProcessedAt(null);
		$this->insert($imipData);
	}

	/**
	 * @throws Exception
	 */
	public function markProcessed(int $messageId, bool $error): void {
		$qb = $this->db->getQueryBuilder();
		$update = $qb->update($this->getTableName())
			->set('error', $qb->createNamedParameter($error, IQueryBuilder::PARAM_BOOL))
			->set('processed_at', $qb->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('imip_message_id', $qb->createNamedParameter($messageId, IQueryBuilder::PARAM_INT))
			);

		$update->executeStatement();

	}

	/**
	 * @throws Exception
	 * @throws Throwable
	 */
	public function markProcessedBulk(Message ...$messages): array {
		$this->db->beginTransaction();

		try {
			foreach ($messages as $message) {
				if (empty($message->getUpdatedFields())) {
					continue;
				}

				$this->markProcessed($message->getId(), $message->isImipError());
			}

			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();

			throw $e;
		}

		return $messages;
	}

}
