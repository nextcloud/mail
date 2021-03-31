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
	 */
	public function find(int $aliasId, string $currentUserId): Alias {
		return $this->aliasMapper->find($aliasId, $currentUserId);
	}

	/**
	 * @param string $currentUserId
	 * @param int $accountId
	 * @param string $alias
	 * @param string $aliasName
	 *
	 * @return Alias
	 * @throws ClientException
	 */
	public function create(string $currentUserId, int $accountId, string $alias, string $aliasName): Alias {
		try {
			$this->mailAccountMapper->find($currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Account $accountId does not exist or no permission to access it");
		}

		$aliasEntity = new Alias();
		$aliasEntity->setAccountId($accountId);
		$aliasEntity->setAlias($alias);
		$aliasEntity->setName($aliasName);

		return $this->aliasMapper->insert($aliasEntity);
	}

	/**
	 * @param int $aliasId
	 * @param String $currentUserId
	 * @return Alias
	 */
	public function delete(int $aliasId, string $currentUserId): Alias {
		$alias = $this->aliasMapper->find($aliasId, $currentUserId);
		$this->aliasMapper->delete($alias);
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
		$this->aliasMapper->deleteAll($accountId);
	}
}
