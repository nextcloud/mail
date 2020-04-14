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

use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\ILogger;
use OCP\Security\ICrypto;

class SmtpConnectivityTester {

	/** @var ConnectivityTester */
	private $connectivityTester;

	/** @var ICrypto */
	private $crypto;

	/** @var SmtpClientFactory */
	private $clientFactory;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $userId;

	/**
	 * @param ConnectivityTester $connectivityTester
	 * @param ICrypto $crypto
	 * @param SmtpClientFactory $clientFactory
	 * @param ILogger $logger
	 * @param string $UserId
	 */
	public function __construct(ConnectivityTester $connectivityTester,
								ICrypto $crypto,
								SmtpClientFactory $clientFactory,
								ILogger $logger,
								$UserId) {
		$this->connectivityTester = $connectivityTester;
		$this->crypto = $crypto;
		$this->clientFactory = $clientFactory;
		$this->logger = $logger;
		$this->userId = $UserId;
	}

	/**
	 * @param MailAccount $account
	 * @param string $host
	 * @param string|array $users
	 * @param string $password
	 * @param bool $withHostPrefix
	 * @return bool
	 */
	public function test(MailAccount $account,
						 string $host,
						 $users,
						 string $password,
						 bool $withHostPrefix = false): bool {
		if (!is_array($users)) {
			$users = [$users];
		}

		// port 25 should be the last one to test
		$ports = [587, 465, 25];
		$protocols = ['ssl', 'tls', 'none'];
		$hostPrefixes = [''];
		if ($withHostPrefix) {
			$hostPrefixes = ['', 'imap.'];
		}
		$encryptedPassword = $this->crypto->encrypt($password);

		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			if (gethostbyname($url) === $url) {
				continue;
			}
			foreach ($ports as $port) {
				if (!$this->connectivityTester->canConnect($url, $port)) {
					continue;
				}
				foreach ($protocols as $protocol) {
					foreach ($users as $user) {
						try {
							$account->setOutboundHost($url);
							$account->setOutboundPort($port);
							$account->setOutboundUser($user);
							$account->setOutboundPassword($encryptedPassword);
							$account->setOutboundSslMode($protocol);

							$this->testStmtpConnection($account);

							$this->logger->info("Test-Account-Successful: $this->userId, $url, $port, $user, $protocol");
							return true;
						} catch (Exception $e) {
							$error = $e->getMessage();
							$this->logger->info("Test-Account-Failed: $this->userId, $url, $port, $user, $protocol -> $error");
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param MailAccount $mailAccount
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	protected function testStmtpConnection(MailAccount $mailAccount): void {
		$account = new Account($mailAccount);
		$smtp = $this->clientFactory->create($account);
		$smtp->getSMTPObject();
	}
}
