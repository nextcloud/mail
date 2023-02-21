<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Db;

use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<SmimeCertificate>
 */
class SmimeCertificateMapper extends QBMapper {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'mail_smime_certificates');
	}

	/**
	 * @param int $id
	 * @param string $userId
	 * @return SmimeCertificate
	 *
	 * @throws DoesNotExistException
	 */
	public function find(int $id, string $userId): SmimeCertificate {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		try {
			return $this->findEntity($qb);
		} catch (MultipleObjectsReturnedException $e) {
			// Not possible due to uniqueness of id
			throw new ServiceException("The impossible has happened", 42, $e);
		}
	}

	/**
	 * @param string $userId
	 * @return SmimeCertificate[]
	 */
	public function findAll(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Find all S/MIME certificates by email address.
	 *
	 * @param string $userId
	 * @param string $emailAddress
	 * @return SmimeCertificate[]
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function findAllByEmailAddress(string $userId, string $emailAddress): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
				$qb->expr()->eq('email_address', $qb->createNamedParameter($emailAddress)),
			);

		return $this->findEntities($qb);
	}

	/**
	 * Find all S/MIME certificates by email addresses
	 *
	 * @param string $userId
	 * @param string[] $emailAddresses
	 * @return SmimeCertificate[]
	 *
	 * @throws \OCP\DB\Exception
	 */
	public function findAllByEmailAddresses(string $userId, array $emailAddresses): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
				$qb->expr()->in('email_address', $qb->createNamedParameter($emailAddresses, IQueryBuilder::PARAM_STR_ARRAY)),
			);

		return $this->findEntities($qb);
	}
}
