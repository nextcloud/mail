<?php

declare(strict_types=1);

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
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

namespace OCA\Mail\Service;

use OCA\Mail\Db\Alias;
use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Db\DoesNotExistException;

class AliasesService {
	/** @var AliasMapper */
	private $aliasMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	public function __construct(AliasMapper $aliasMapper, MailAccountMapper $mailAccountMapper) {
		$this->aliasMapper = $aliasMapper;
		$this->mailAccountMapper = $mailAccountMapper;
	}

	/**
	 * @param int $accountId
	 * @param String $currentUserId
	 * @return Alias[]
	 */
	public function findAll(int $accountId, string $currentUserId): array {
		return $this->aliasMapper->findAll($accountId, $currentUserId);
	}

	/**
	 * @param int $aliasId
	 * @param string $currentUserId
	 * @return Alias
	 * @throws DoesNotExistException
	 */
	public function find(int $aliasId, string $currentUserId): Alias {
		return $this->aliasMapper->find($aliasId, $currentUserId);
	}

	/**
	 * @param string $userId
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return Alias
	 * @throws DoesNotExistException
	 */
	public function create(string $userId, int $accountId, string $alias, string $aliasName): Alias {
		$this->mailAccountMapper->find($userId, $accountId);

		$aliasEntity = new Alias();
		$aliasEntity->setAccountId($accountId);
		$aliasEntity->setAlias($alias);
		$aliasEntity->setName($aliasName);

		return $this->aliasMapper->insert($aliasEntity);
	}

	/**
	 * @throws ClientException
	 * @throws DoesNotExistException
	 */
	public function delete(string $userId, int $aliasId): Alias {
		$entity = $this->aliasMapper->find($aliasId, $userId);

		if ($entity->isProvisioned()) {
			throw new ClientException('Deleting a provisioned alias is not allowed.');
		}

		return $this->aliasMapper->delete($entity);
	}

	/**
	 * Deletes all aliases of an account.
	 *
	 * @param int $accountId the account which aliases will be deleted
	 * @param string $currentUserId the user whom the account belongs to
	 *
	 * @return void
	 */
	public function deleteAll($accountId): void {
		$this->aliasMapper->deleteAll($accountId);
	}

	/**
	 * Update alias and name
	 *
	 * @throws DoesNotExistException
	 */
	public function update(string $userId,
						   int $aliasId,
						   string $alias,
						   string $aliasName,
						   ?int $smimeCertificateId): Alias {
		$entity = $this->aliasMapper->find($aliasId, $userId);

		if (!$entity->isProvisioned()) {
			$entity->setAlias($alias);
		}
		$entity->setName($aliasName);
		$entity->setSmimeCertificateId($smimeCertificateId);

		return $this->aliasMapper->update($entity);
	}

	/**
	 * Update signature for alias
	 *
	 * @throws DoesNotExistException
	 */
	public function updateSignature(string $userId, int $aliasId, string $signature = null): Alias {
		$entity = $this->find($aliasId, $userId);
		$entity->setSignature($signature);
		return $this->aliasMapper->update($entity);
	}
}
