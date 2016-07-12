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

use OCA\Mail\Service\Logger;
use OCP\IConfig;
use OCP\ISession;

class DefaultAccountManager {

	/** @var IConfig */
	private $config;

	/** @var ISession */
	private $session;

	/** @var Logger */
	private $logger;

	/**
	 * @param IConfig $config
	 * @param ISession $session
	 * @param Logger $logger
	 */
	public function __construct(IConfig $config, ISession $session, Logger $logger) {
		$this->config = $config;
		$this->session = $session;
		$this->logger = $logger;
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
			return $config;
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

}
