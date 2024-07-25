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
 * @template-extends QBMapper<InternalAddress>
 */
class InternalAddressMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_internal_address');
	}

	public function exists(string $uid, string $address): bool {

		$emailObject = new \Horde_Mail_Rfc822_Address($address);
		$host = $emailObject->host;
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->orX(
					$qb->expr()->andX(
						$qb->expr()->eq('address', $qb->createNamedParameter($address)),
						$qb->expr()->eq('type', $qb->createNamedParameter('individual'))
					),
					$qb->expr()->andX(
						$qb->expr()->eq('address', $qb->createNamedParameter($host)),
						$qb->expr()->eq('type', $qb->createNamedParameter('domain'))
					)
				),
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid))
			);

		$rows = $this->findEntities($select);

		return $rows !== [];
	}

	public function create(string $uid, string $address, string $type): void {
		$qb = $this->db->getQueryBuilder();

		$insert = $qb->insert($this->getTableName())
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'address' => $qb->createNamedParameter($address),
				'type' => $qb->createNamedParameter($type),
			]);

		$insert->executeStatement();
	}

	public function remove(string $uid, string $address, string $type): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter($address)),
				$qb->expr()->eq('type', $qb->createNamedParameter($type))
			);

		$delete->executeStatement();
	}

	/**
	 * @param string $uid
	 * @return InternalAddress[]
	 */
	public function findAll(string $uid): array {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)));
		return $this->findEntities($select);
	}

	public function find(string $uid, string $address): ?InternalAddress {
		$qb = $this->db->getQueryBuilder();
		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('address', $qb->createNamedParameter($address))
			);
		return $this->findEntity($select);
	}
}
