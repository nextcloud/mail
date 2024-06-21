<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Recipient>
 */
class RecipientMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_recipients');
	}

	/**
	 * @returns Recipient[]
	 */
	public function findByLocalMessageId(int $localMessageId): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($query);
	}

	/**
	 * @return Recipient[]
	 */
	public function findByLocalMessageIds(array $localMessageIds): array {
		if ($localMessageIds === []) {
			return [];
		}
		$qb = $this->db->getQueryBuilder();
		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('local_message_id', $qb->createNamedParameter($localMessageIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY)
			);

		return $this->findEntities($query);
	}

	public function deleteForLocalMessage(int $localMessageId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('local_message_id', $qb->createNamedParameter($localMessageId, IQueryBuilder::PARAM_INT))
			);
		$qb->executeStatement();
	}

	/**
	 * @param Recipient[] $recipients
	 */
	public function saveRecipients(int $localMessageId, array $recipients): void {
		foreach ($recipients as $recipient) {
			$recipient->setLocalMessageId($localMessageId);
			$this->insert($recipient);
		}
	}

	/**
	 * @param int $localMessageId
	 * @param Recipient[] $oldRecipients
	 * @param Recipient[] $to
	 * @param Recipient[] $cc
	 * @param Recipient[] $bcc
	 * @return void
	 */
	public function updateRecipients(int $localMessageId, array $oldRecipients, array $to, array $cc, array $bcc): void {
		if (empty(array_merge($to, $cc, $bcc))) {
			// No recipients set anymore. Remove any old ones.
			$this->deleteForLocalMessage($localMessageId);
			return;
		}

		if ($oldRecipients === []) {
			// No need for a diff, save and return
			$this->saveRecipients($localMessageId, $to);
			$this->saveRecipients($localMessageId, $cc);
			$this->saveRecipients($localMessageId, $bcc);
			return;
		}

		// Get old Recipients split per their types
		$oldTo = array_filter($oldRecipients, static function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_TO;
		});
		$oldCc = array_filter($oldRecipients, static function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_CC;
		});
		$oldBcc = array_filter($oldRecipients, static function ($recipient) {
			return $recipient->getType() === Recipient::TYPE_BCC;
		});

		// To - add
		$newTo = array_udiff($to, $oldTo, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		if ($newTo !== []) {
			$this->saveRecipients($localMessageId, $newTo);
		}

		$toRemove = array_udiff($oldTo, $to, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		foreach ($toRemove as $r) {
			$this->delete($r);
		}

		// CC
		$newCC = array_udiff($cc, $oldCc, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		if ($newCC !== []) {
			$this->saveRecipients($localMessageId, $newCC);
		}

		$ccRemove = array_udiff($oldCc, $cc, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		foreach ($ccRemove as $r) {
			$this->delete($r);
		}

		// BCC
		$newBcc = array_udiff($bcc, $oldBcc, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		if ($newBcc !== []) {
			$this->saveRecipients($localMessageId, $newBcc);
		}

		$bccRemove = array_udiff($oldBcc, $bcc, static function (Recipient $a, Recipient $b) {
			return strcmp($a->getEmail(), $b->getEmail());
		});
		foreach ($bccRemove as $r) {
			$this->delete($r);
		}
	}
}
