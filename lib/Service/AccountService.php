<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\DefaultAccount\Manager;
use OCP\AppFramework\Db\DoesNotExistException;

class AccountService {

	/** @var MailAccountMapper */
	private $mapper;

	/**
	 * Cache accounts for multiple calls to 'findByUserId'
	 *
	 * @var Account[]
	 */
	private $accounts;

	/** @var Manager */
	private $defaultAccountManager;

	/** @var AliasesService */
	private $aliasesService;

	public function __construct(MailAccountMapper $mapper,
								Manager $defaultAccountManager,
								AliasesService $aliasesService) {
		$this->mapper = $mapper;
		$this->defaultAccountManager = $defaultAccountManager;
		$this->aliasesService = $aliasesService;
	}

	/**
	 * @param string $currentUserId
	 * @return Account[]
	 */
	public function findByUserId(string $currentUserId): array {
		if ($this->accounts === null) {
			$accounts = array_map(function ($a) {
				return new Account($a);
			}, $this->mapper->findByUserId($currentUserId));

			$defaultAccount = $this->defaultAccountManager->getDefaultAccount();
			if (!is_null($defaultAccount)) {
				$accounts[] = new Account($defaultAccount);
			}

			$this->accounts = $accounts;
		}

		return $this->accounts;
	}

	/**
	 * @param string $uid
	 * @param int $accountId
	 *
	 * @return Account
	 * @throws DoesNotExistException
	 */
	public function find(string $uid, int $accountId): Account {
		if ($this->accounts !== null) {
			foreach ($this->accounts as $account) {
				if ($account->getId() === $accountId) {
					return $account;
				}
			}
			throw new DoesNotExistException("Invalid account id <$accountId>");
		}

		if ($accountId === Manager::ACCOUNT_ID) {
			$defaultAccount = $this->defaultAccountManager->getDefaultAccount();
			if (is_null($defaultAccount)) {
				throw new DoesNotExistException('Default account config missing');
			}
			return new Account($defaultAccount);
		}
		return new Account($this->mapper->find($uid, $accountId));
	}

	/**
	 * @param int $accountId
	 */
	public function delete(string $currentUserId, int $accountId): void {
		if ($accountId === Manager::ACCOUNT_ID) {
			return;
		}

		$mailAccount = $this->mapper->find($currentUserId, $accountId);
		$this->aliasesService->deleteAll($accountId);
		$this->mapper->delete($mailAccount);
	}

	/**
	 * @param MailAccount $newAccount
	 * @return MailAccount
	 */
	public function save(MailAccount $newAccount): MailAccount {
		return $this->mapper->save($newAccount);
	}

	public function updateSignature(int $id, string $uid, string $signature = null): void {
		$account = $this->find($uid, $id);
		if ($account === null) {
			throw new ServiceException('Account does not exist or user is not permitted to change it');
		}
		$mailAccount = $account->getMailAccount();
		$mailAccount->setSignature($signature);
		$this->mapper->save($mailAccount);
	}

}
