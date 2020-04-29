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

use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\BackgroundJob\TrainImportanceClassifierJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use function array_map;

class AccountService {

	/** @var MailAccountMapper */
	private $mapper;

	/**
	 * Cache accounts for multiple calls to 'findByUserId'
	 *
	 * @var Account[]
	 */
	private $accounts;

	/** @var AliasesService */
	private $aliasesService;

	/** @var IJobList */
	private $jobList;

	public function __construct(MailAccountMapper $mapper,
								AliasesService $aliasesService,
								IJobList $jobList) {
		$this->mapper = $mapper;
		$this->aliasesService = $aliasesService;
		$this->jobList = $jobList;
	}

	/**
	 * @param string $currentUserId
	 * @return Account[]
	 */
	public function findByUserId(string $currentUserId): array {
		if ($this->accounts === null) {
			return $this->accounts = array_map(function ($a) {
				return new Account($a);
			}, $this->mapper->findByUserId($currentUserId));
			;
		}

		return $this->accounts;
	}

	/**
	 * @param int $id
	 *
	 * @return Account
	 * @throws DoesNotExistException
	 */
	public function findById(int $id): Account {
		return new Account($this->mapper->findById($id));
	}

	/**
	 * @param string $uid
	 * @param int $accountId
	 *
	 * @return Account
	 * @throws ClientException
	 */
	public function find(string $uid, int $accountId): Account {
		if ($this->accounts !== null) {
			foreach ($this->accounts as $account) {
				if ($account->getId() === $accountId) {
					return $account;
				}
			}
			throw new ClientException("Account $accountId does not exist or you don\'t have permission to access it");
		}

		try {
			return new Account($this->mapper->find($uid, $accountId));
		} catch (DoesNotExistException $e) {
			throw new ClientException("Account $accountId does not exist or you don\'t have permission to access it");
		}
	}

	/**
	 * @param int $accountId
	 *
	 * @throws ClientException
	 */
	public function delete(string $currentUserId, int $accountId): void {
		try {
			$mailAccount = $this->mapper->find($currentUserId, $accountId);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Account $accountId does not exist", 0, $e);
		}
		$this->aliasesService->deleteAll($accountId);
		$this->mapper->delete($mailAccount);
	}

	/**
	 * @param MailAccount $newAccount
	 * @return MailAccount
	 */
	public function save(MailAccount $newAccount): MailAccount {
		$newAccount = $this->mapper->save($newAccount);

		// Insert background jobs for this account
		$this->jobList->add(SyncJob::class, ['accountId' => $newAccount->getId()]);
		$this->jobList->add(TrainImportanceClassifierJob::class, ['accountId' => $newAccount->getId()]);

		return $newAccount;
	}

	/**
	 * @param int $id
	 * @param string $uid
	 * @param string|null $signature
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
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
