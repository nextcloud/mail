<?php

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
use OCP\IL10N;

class AccountService {

	/** @var MailAccountMapper */
	private $mapper;

	/**
	 * Cache accounts for multiple calls to 'findByUserId'
	 *
	 * @var IAccount[]
	 */
	private $accounts;

	/** @var IL10N */
	private $l10n;

	/**
	 * @param MailAccountMapper $mapper
	 * @param IL10N $l10n
	 */
	public function __construct(MailAccountMapper $mapper, IL10N $l10n) {
		$this->mapper = $mapper;
		$this->l10n = $l10n;
	}

	/**
	 * @param string $currentUserId
	 * @return IAccount[]
	 */
	public function findByUserId($currentUserId) {
		if ($this->accounts === null) {
			$accounts = $this->mapper->findByUserId($currentUserId);
			$accounts = array_map(function($a) {
				return new Account($a);
			}, $accounts);
			$this->accounts = $accounts;
		}

		return $this->accounts;
	}

	/**
	 * @param $currentUserId
	 * @param $accountId
	 * @return IAccount
	 */
	public function find($currentUserId, $accountId) {
		if ($this->accounts !== null) {
			foreach ($this->accounts as $account) {
				if ($account->getId() === $accountId) {
					return $account;
				}
			}
			throw new Exception("Invalid account id <$accountId>");
		}

		if ((int)$accountId === UnifiedAccount::ID) {
			return $this->buildUnifiedAccount($currentUserId);
		}
		return new Account($this->mapper->find($currentUserId, $accountId));
	}

	private function moveMessageOnSameAccount(IAccount $account, $sourceFolderId,
		$destFolderId, $messageId) {
		$account->moveMessage(base64_decode($sourceFolderId), $messageId, base64_decode($destFolderId));
	}

	public function moveMessage($accountId, $folderId, $id, $destAccountId,
		$destFolderId, $userId) {
		$sourceAccount = $this->find($userId, $accountId);
		$destAccount = $this->find($userId, $destAccountId);

		if ($sourceAccount->getId() === $destAccount->getId()) {
			$this->moveMessageOnSameAccount($sourceAccount, $folderId, $destFolderId, $id);
		} else {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
	}

	/**
	 * @param int $accountId
	 */
	public function delete($currentUserId, $accountId) {
		if ((int)$accountId === UnifiedAccount::ID) {
			return;
		}
		$mailAccount = $this->mapper->find($currentUserId, $accountId);
		$this->mapper->delete($mailAccount);
	}

	/**
	 * @param $newAccount
	 * @return MailAccount
	 */
	public function save($newAccount) {
		return $this->mapper->save($newAccount);
	}

	private function buildUnifiedAccount($userId) {
		return new UnifiedAccount($this, $userId, $this->l10n);
	}
}
