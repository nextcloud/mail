<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\BackgroundJob\QuotaJob;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\BackgroundJob\TrainImportanceClassifierJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use function array_map;

class AccountService {
	/** @var MailAccountMapper */
	private $mapper;

	/**
	 * Cache accounts for multiple calls to 'findByUserId'
	 *
	 * @var array<string, Account[]>
	 */
	private array $accounts = [];

	/** @var AliasesService */
	private $aliasesService;

	/** @var IJobList */
	private $jobList;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	public function __construct(MailAccountMapper $mapper,
		AliasesService $aliasesService,
		IJobList $jobList,
		IMAPClientFactory $imapClientFactory) {
		$this->mapper = $mapper;
		$this->aliasesService = $aliasesService;
		$this->jobList = $jobList;
		$this->imapClientFactory = $imapClientFactory;
	}

	/**
	 * @param string $currentUserId
	 * @return Account[]
	 */
	public function findByUserId(string $currentUserId): array {
		if (!isset($this->accounts[$currentUserId])) {
			$this->accounts[$currentUserId] = array_map(static function ($a) {
				return new Account($a);
			}, $this->mapper->findByUserId($currentUserId));
		}

		return $this->accounts[$currentUserId];
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
	 * Finds a mail account by user id and mail address
	 *
	 * @since 4.0.0
	 *
	 * @param string $userId system user id
	 * @param string $address mail address (e.g. test@example.com)
	 *
	 * @return Account[]
	 */
	public function findByUserIdAndAddress(string $userId, string $address): array {
		// evaluate if cached accounts collection already exists
		if (isset($this->accounts[$userId])) {
			// initialize temporary collection
			$list = [];
			// iterate through accounts and find accounts matching mail address
			foreach ($this->accounts[$userId] as $account) {
				if ($account->getEmail() === $address) {
					$list[] = $account;
				}
			}
			return $list;
		}
		// if cached accounts collection did not exist retrieve account details directly from the data store
		return array_map(static function ($a) {
			return new Account($a);
		}, $this->mapper->findByUserIdAndAddress($userId, $address));
	}

	/**
	 * @param string $userId
	 * @param int $id
	 *
	 * @return Account
	 * @throws ClientException
	 */
	public function find(string $userId, int $id): Account {
		if (isset($this->accounts[$userId])) {
			foreach ($this->accounts[$userId] as $account) {
				if ($account->getId() === $id) {
					return $account;
				}
			}
			throw new ClientException("Account $id does not exist or you don\'t have permission to access it");
		}

		try {
			return new Account($this->mapper->find($userId, $id));
		} catch (DoesNotExistException $e) {
			throw new ClientException("Account $id does not exist or you don\'t have permission to access it");
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
	 * @param int $accountId
	 *
	 * @throws ClientException
	 */
	public function deleteByAccountId(int $accountId): void {
		try {
			$mailAccount = $this->mapper->findById($accountId);
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
		$this->jobList->add(PreviewEnhancementProcessingJob::class, ['accountId' => $newAccount->getId()]);
		$this->jobList->add(QuotaJob::class, ['accountId' => $newAccount->getId()]);

		return $newAccount;
	}

	public function update(MailAccount $account): MailAccount {
		return $this->mapper->update($account);
	}

	/**
	 * @param int $id
	 * @param string $uid
	 * @param string|null $signature
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function updateSignature(int $id, string $uid, ?string $signature = null): void {
		$account = $this->find($uid, $id);
		$mailAccount = $account->getMailAccount();
		$mailAccount->setSignature($signature);
		$this->mapper->save($mailAccount);
	}

	/**
	 * @return MailAccount[]
	 */
	public function getAllAcounts(): array {
		return $this->mapper->getAllAccounts();
	}


	/**
	 * @param string $currentUserId
	 * @param int $accountId
	 * @return bool
	 */

	public function testAccountConnection(string $currentUserId, int $accountId) :bool {
		$account = $this->find($currentUserId, $accountId);
		try {
			$client = $this->imapClientFactory->getClient($account);
			$client->close();
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}
}
