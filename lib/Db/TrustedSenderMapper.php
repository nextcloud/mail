<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class TrustedSenderMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_trusted_senders');
	}

	public function exists(string $uid, string $email): bool {
		$qb = $this->db->getQueryBuilder();

		$select = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter($email))
			);

		/** @var TrustedSender[] $rows */
		$rows = $this->findEntities($select);

		return !empty($rows);
	}

	public function create(string $uid, string $email): void {
		$qb = $this->db->getQueryBuilder();

		$insert = $qb->insert($this->getTableName())
			->values([
				'user_id' => $qb->createNamedParameter($uid),
				'email' => $qb->createNamedParameter($email),
			]);

		$insert->execute();
	}

	public function remove(string $uid, string $email): void {
		$qb = $this->db->getQueryBuilder();

		$delete = $qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($uid)),
				$qb->expr()->eq('email', $qb->createNamedParameter($email))
			);

		$delete->execute();
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
