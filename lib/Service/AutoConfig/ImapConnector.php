<?php

declare(strict_types=1);

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

use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\ILogger;
use OCP\Security\ICrypto;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;

class ImapConnector {

	/** @var ICrypto */
	private $crypto;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $userId;

	/** @var IMAPClientFactory */
	private $clientFactory;

	public function __construct(ICrypto $crypto,
								ILogger $logger,
								IMAPClientFactory $clientFactory,
								string $UserId = null) {
		$this->crypto = $crypto;
		$this->logger = $logger;
		$this->userId = $UserId;
		$this->clientFactory = $clientFactory;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @param string $host
	 * @param int $port
	 * @param string $encryptionProtocol
	 * @param string $user
	 * @return MailAccount
	 *
	 * @throws \Horde_Imap_Client_Exception
	 */
	public function connect(string $email,
							string $password,
							string $name,
							string $host,
							int $port,
							string $encryptionProtocol,
							string $user): MailAccount {
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
		$client = $this->clientFactory->getClient($a);
		$client->login();
		$this->logger->info("Test-Account-Successful: $this->userId, $host, $port, $user, $encryptionProtocol");
		return $account;
	}
}
