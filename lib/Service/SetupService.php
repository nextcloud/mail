<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\ILogger;
use OCP\Security\ICrypto;

class SetupService {

	/** @var AutoConfig */
	private $autoConfig;

	/** @var AccountService */
	private $accountService;

	/** @var ICrypto */
	private $crypto;

	/** @var SmtpClientFactory */
	private $smtpClientFactory;

	/** var ILogger */
	private $logger;

	/**
	 * @param AutoConfig $autoConfig
	 * @param AccountService $accountService
	 * @param ICrypto $crypto
	 * @param ILogger $logger
	 */
	public function __construct(AutoConfig $autoConfig, AccountService $accountService, ICrypto $crypto, SmtpClientFactory $smtpClientFactory, ILogger $logger) {
		$this->autoConfig = $autoConfig;
		$this->accountService = $accountService;
		$this->crypto = $crypto;
		$this->smtpClientFactory = $smtpClientFactory;
		$this->logger = $logger;
	}

	/**
	 * @param string $accountName
	 * @param string $emailAddress
	 * @param string $password
	 * @return Account|null
	 */
	public function createNewAutoConfiguredAccount($accountName, $emailAddress, $password) {
		$this->logger->info('setting up auto detected account');
		$mailAccount = $this->autoConfig->createAutoDetected($emailAddress, $password, $accountName);
		if (is_null($mailAccount)) {
			return null;
		}

		$this->accountService->save($mailAccount);

		return new Account($mailAccount);
	}

	/**
	 * @param string $accountName
	 * @param string $emailAddress
	 * @param string $imapHost
	 * @param int $imapPort
	 * @param string $imapSslMode
	 * @param string $imapUser
	 * @param string $imapPassword
	 * @param string $smtpHost
	 * @param int $smtpPort
	 * @param string $smtpSslMode
	 * @param string $smtpUser
	 * @param string $smtpPassword
	 * @param string $uid
	 *
	 * @throws ServiceException
	 */
	public function createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $uid, $accountId = null) {
		$this->logger->info('Setting up manually configured account');
		$newAccount = new MailAccount([
			'accountId' => $accountId,
			'accountName' => $accountName,
			'emailAddress' => $emailAddress,
			'imapHost' => $imapHost,
			'imapPort' => $imapPort,
			'imapSslMode' => $imapSslMode,
			'imapUser' => $imapUser,
			'imapPassword' => $imapPassword,
			'smtpHost' => $smtpHost,
			'smtpPort' => $smtpPort,
			'smtpSslMode' => $smtpSslMode,
			'smtpUser' => $smtpUser,
			'smtpPassword' => $smtpPassword
		]);
		$newAccount->setUserId($uid);
		$newAccount->setInboundPassword($this->crypto->encrypt($newAccount->getInboundPassword()));
		$newAccount->setOutboundPassword($this->crypto->encrypt($newAccount->getOutboundPassword()));

		$account = new Account($newAccount);
		$this->logger->debug('Connecting to account {account}', ['account' => $newAccount->getEmail()]);
		$transport = $this->smtpClientFactory->create($account);
		$account->testConnectivity($transport);

		if ($newAccount) {
			$this->accountService->save($newAccount);
			$this->logger->debug("account created " . $newAccount->getId());
			return new Account($newAccount);
		}

		return null;
	}
}
