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
use OCA\Mail\Service\DefaultAccount\Manager;
use OCP\IL10N;

class AccountService {

	/** @var MailAccountMapper */
	private $mapper;

	/**
	 * Cache accounts for multiple calls to 'findByUserId'
	 *
	 * @var Account[]
	 */
	private $accounts;

	/** @var IL10N */
	private $l10n;

	/** @var Manager */
	private $defaultAccountManager;

	/**
	 * @param MailAccountMapper $mapper
	 * @param IL10N $l10n
	 */
	public function __construct(MailAccountMapper $mapper, IL10N $l10n,
		Manager $defaultAccountManager) {
		$this->mapper = $mapper;
		$this->l10n = $l10n;
		$this->defaultAccountManager = $defaultAccountManager;
	}

	/**
	 * @param string $currentUserId
	 * @return Account[]
	 */
	public function findByUserId($currentUserId) {
		if ($this->accounts === null) {
			$accounts = array_map(function($a) {
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
	 * @param $currentUserId
	 * @param $accountId
	 * @return Account
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

		if ((int) $accountId === Manager::ACCOUNT_ID) {
			$defaultAccount = $this->defaultAccountManager->getDefaultAccount();
			if (is_null($defaultAccount)) {
				throw new Exception('Default account config missing');
			}
			return new Account($defaultAccount);
		}
		return new Account($this->mapper->find($currentUserId, $accountId));
	}

	/**
	 * @param int $accountId
	 */
	public function delete($currentUserId, $accountId) {
		if ((int) $accountId === Manager::ACCOUNT_ID) {
			return;
		}
		$mailAccount = $this->mapper->find($currentUserId, $accountId);
		$this->mapper->delete($mailAccount);
	}

	/**
	 * @param MailAccount $newAccount
	 * @return MailAccount
	 */
	public function save(MailAccount $newAccount) {
		return $this->mapper->save($newAccount);
	}

}
