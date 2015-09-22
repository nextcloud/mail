<?php

namespace OCA\Mail\Service\AutoConfig;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use OCP\Security\ICrypto;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;

class ImapConnector {

	/**
	 * @var Crypto
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
	 * @param type $UserId
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
