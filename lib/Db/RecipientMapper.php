<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		if (empty($localMessageIds)) {
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
		$qb->execute();
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

		if (empty($oldRecipients)) {
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
		if (!empty($newTo)) {
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
		if (!empty($newCC)) {
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
		if (!empty($newBcc)) {
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
