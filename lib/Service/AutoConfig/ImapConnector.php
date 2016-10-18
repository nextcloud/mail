<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\Mail\Service\AutoConfig;

use OCP\Security\ICrypto;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;

class ImapConnector {

	/**
	 * @var ICrypto
	 */
	private $crypto;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $userId;

	/**
	 * @param ICrypto $crypto
	 * @param Logger $logger
	 * @param string $UserId
	 */
	public function __construct(ICrypto $crypto, Logger $logger, $UserId) {
		$this->crypto = $crypto;
		$this->logger = $logger;
		$this->userId = $UserId;
	}

	/**
	 * @param $email
	 * @param $password
	 * @param $name
	 * @param $host
	 * @param $port
	 * @param string|null $encryptionProtocol
	 * @param $user
	 * @return MailAccount
	 */
	public function connect($email, $password, $name, $host, $port,
		$encryptionProtocol, $user) {

		$account = new MailAccount();
		$account->setUserId($this->userId);
		$account->setName($name);
		$account->setEmail($email);
		$account->setInboundHost($host);
		$account->setInboundPort($port);
		$account->setInboundSslMode($encryptionProtocol);
		$account->setInboundUser($user);
		$password = $this->crypto->encrypt($password);
		$account->setInboundPassword($password);

		$a = new Account($account);
		$a->getImapConnection();
		$this->logger->info("Test-Account-Successful: $this->userId, $host, $port, $user, $encryptionProtocol");
		return $account;
	}

}
