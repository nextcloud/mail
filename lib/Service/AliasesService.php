<?php

declare(strict_types=1);

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

class AliasesService {

	/** @var AliasMapper */
	private $mapper;

	public function __construct(AliasMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @param int $accountId
	 * @param String $currentUserId
	 * @return Alias[]
	 */
	public function findAll(int $accountId, string $currentUserId): array {
		return $this->mapper->findAll($accountId, $currentUserId);
	}

	/**
	 * @param int $aliasId
	 * @param string $currentUserId
	 * @return Alias
	 */
	public function find(int $aliasId, string $currentUserId): Alias {
		return $this->mapper->find($aliasId, $currentUserId);
	}

	/**
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 * @return Alias
	 */
	public function create(int $accountId, string $alias, string $aliasName) {
		$aliasEntity = new Alias();
		$aliasEntity->setAccountId($accountId);
		$aliasEntity->setAlias($alias);
		$aliasEntity->setName($aliasName);
		return $this->mapper->insert($aliasEntity);
	}

	/**
	 * @param int $aliasId
	 * @param String $currentUserId
	 * @return Alias
	 */
	public function delete(int $aliasId, string $currentUserId): Alias {
		$alias = $this->mapper->find($aliasId, $currentUserId);
		$this->mapper->delete($alias);
		return $alias;
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
		$this->mapper->deleteAll($accountId);
	}
}
