<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

namespace OCA\Mail\Service\DefaultAccount;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\Logger;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;

class DefaultAccountManager {

	/** @var IConfig */
	private $config;

	/** @var ISession */
	private $session;

	/** @var Logger */
	private $logger;

	/** @var MailAccountMapper */
	private $mapper;

	/** @var IUserSession */
	private $userSession;

	/** @var ICrypto */
	private $crypto;

	/**
	 * @param IConfig $config
	 * @param ISession $session
	 * @param Logger $logger
	 * @param MailAccountMapper $mapper
	 * @param IUserSession $userSession
	 * @param ICrypto $crypto
	 */
	public function __construct(IConfig $config, ISession $session, Logger $logger,
		MailAccountMapper $mapper, IUserSession $userSession, ICrypto $crypto) {
		$this->config = $config;
		$this->session = $session;
		$this->logger = $logger;
		$this->mapper = $mapper;
		$this->userSession = $userSession;
		$this->crypto = $crypto;
	}

	/**
	 * @return array
	 */
	private function getConfig() {
		$config = $this->config->getSystemValue('app.mail.accounts.default', null);
		if (is_null($config)) {
			$this->logger->debug('no default config found');
			return null;
		} else {
			$this->logger->debug('default config to create a default account found');
			// TODO: check if config is complete
			return new Config($config);
		}
	}

	/**
	 * Save the login password to the session to create a default account in a
	 * sub-sequent request
	 *
	 * @param string $password
	 */
	public function saveLoginPassword($password) {
		$this->session->set('mail_default_account_password', $password);
	}

	public function createOrUpdateDefaultAccount() {
		$config = $this->getConfig();
		if (is_null($config)) {
			return;
		}

		$user = $this->userSession->getUser();
		if (is_null($user)) {
			return;
		}

		$accounts = $this->mapper->findByEmail($config->buildEmail($user), $user->getUID());
		if (empty($accounts)) {
			$this->createDefaultAccount($user, $config);
		} else if (count($accounts) === 1) {
			$this->updateDefaultAccount($accounts[0], $user, $config);
		} else {
			$this->logger->debug('unexpected state: more than one account with the default email found for user ' . $user->getUID());
		}
	}

	private function createDefaultAccount(IUser $user, Config $config) {
		if (!$this->session->exists('mail_default_account_password')) {
			return;
		}
		$this->logger->info('creating default account for user ' . $user->getUID());
		$password = $this->crypto->encrypt($this->session->get('mail_default_account_password'));

		$account = new MailAccount();
		$account->setUserId($user->getUID());
		$account->setEmail($config->buildEmail($user));
		$account->setName($user->getDisplayName());

		$account->setInboundUser($config->buildImapUser($user));
		$account->setInboundHost($config->getImapHost());
		$account->setInboundPort($config->getImapPort());
		$account->setInboundSslMode($config->getImapSslMode());
		$account->setInboundPassword($password);

		$account->setOutboundUser($config->buildSmtpUser($user));
		$account->setOutboundHost($config->getSmtpHost());
		$account->setOutboundPort($config->getSmtpPort());
		$account->setOutboundSslMode($config->getSmtpSslMode());
		$account->setOutboundPassword($password);

		$this->mapper->insert($account);
	}

	/**
	 * @todo update imap/smtp config if it changed
	 *
	 * @param MailAccount $account
	 * @param IUser $user
	 * @param Config $config
	 */
	private function updateDefaultAccount(MailAccount $account) {
		$password = $this->crypto->encrypt($this->session->get('mail_default_account_password'));
		$needsUpdate = false;

		if ($account->getInboundPassword() !== $password) {
			$account->setInboundPassword($password);
			$needsUpdate = true;
		}
		if ($account->getOutboundPassword() !== $password) {
			$account->setOutboundPassword($password);
			$needsUpdate = true;
		}

		if ($needsUpdate) {
			$this->mapper->update($account);
		}
	}

}
