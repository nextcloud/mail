<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<TrustedSender>
 */
class TrustedSenderMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_trusted_senders');
	}

	public function exists(string $uid, string $email): bool {
		$emailObject = new \Horde_Mail_Rfc822_Address($email);
		$host = $emailObject->host;
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->orX(
					$qb->expr()->andX(
						$qb->expr()->eq('email', $qb->createNamedParameter($email)),
						$qb->expr()->eq('type', $qb->createNamedParameter('individual'))
					),
					$qb->expr()->andX(
						$qb->expr()->eq('email', $qb->createNamedParameter($host)),
						$qb->expr()->eq('type', $qb->createNamedParameter('domain'))
					)
				),
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid))
			);

		$rows = $this->findEntities($select);

		return $rows !== [];
	}

	public function create(string $uid, string $email, string $type): void {
		$qb = $this->db->getQueryBuilder();

		$insert = $qb->insert($this->getTableName())
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter($email),
				'type' => $qb->createNamedParameter($type),
			]);

		$insert->executeStatement();
	}

	public function remove(string $uid, string $email, string $type): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter($email)),
				$qb->expr()->eq('type', $qb->createNamedParameter($type))
			);

		$delete->executeStatement();
	}

	/**
	 * @param string $uid
	 * @return TrustedSender[]
	 */
	public function findAll(string $uid): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)));
		return $this->findEntities($select);
	}
}
