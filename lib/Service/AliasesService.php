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
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;

class AliasesService {
	/** @var AliasMapper */
	private $aliasMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	private ProvisioningMapper $provisioningMapper;
	private IUserManager $userManager;

	public function __construct(
		AliasMapper $aliasMapper,
		MailAccountMapper $mailAccountMapper,
		ProvisioningMapper $provisioningMapper,
		IUserManager $userManager,
	) {
		$this->aliasMapper = $aliasMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->provisioningMapper = $provisioningMapper;
		$this->userManager = $userManager;
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
	 * Get all aliases with deletable flag included.
	 *
	 * @return list<array>
	 */
	public function findAllWithDeletable(int $accountId, string $currentUserId): array {
		$aliases = $this->findAll($accountId, $currentUserId);
		return array_map(function ($alias) use ($currentUserId) {
			$data = $alias->jsonSerialize();
			$data['deletable'] = $this->isAliasDeletable($alias, $currentUserId);
			return $data;
		}, $aliases);
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

		if ($entity->isProvisioned() && !$this->isAliasDeletable($entity, $userId)) {
			throw new ClientException('Deleting a provisioned alias is not allowed.');
		}

		return $this->aliasMapper->delete($entity);
	}

	/**
	 * Check if a provisioned alias can be deleted.
	 *
	 * A provisioned alias is deletable if its name is no longer in the
	 * current provisioning name templates. This allows users to clean up
	 * aliases after an admin removes a name template.
	 */
	public function isAliasDeletable(Alias $alias, string $userId): bool {
		// Non-provisioned aliases are always deletable
		if (!$alias->isProvisioned()) {
			return true;
		}

		$account = $this->mailAccountMapper->find($userId, $alias->getAccountId());
		$provisioningId = $account->getProvisioningId();

		// No provisioning config means alias shouldn't exist as provisioned - allow deletion
		if ($provisioningId === null) {
			return true;
		}

		$provisioning = $this->provisioningMapper->get($provisioningId);
		if ($provisioning === null) {
			return true;
		}

		$user = $this->userManager->get($userId);
		if ($user === null) {
			return false;
		}

		$validNames = $provisioning->buildNames($user);

		// If alias name is NOT in valid names, it's deletable
		return !in_array($alias->getName(), $validNames, true);
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
