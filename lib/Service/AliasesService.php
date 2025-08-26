<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	 * @return list<Alias>
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
	 * @param string $aliasEmail
	 * @param string $userId
	 * @return Alias
	 * @throws DoesNotExistException
	 */
	public function findByAliasAndUserId(string $aliasEmail, string $userId): Alias {
		return $this->aliasMapper->findByAlias($aliasEmail, $userId);
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
	public function updateSignature(string $userId, int $aliasId, ?string $signature = null): Alias {
		$entity = $this->find($aliasId, $userId);
		$entity->setSignature($signature);
		return $this->aliasMapper->update($entity);
	}
}
