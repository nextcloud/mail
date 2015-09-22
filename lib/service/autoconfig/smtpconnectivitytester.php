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
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;

class SmtpConnectivityTester extends ConnectivityTester {

	/**
	 * @var ICrypto
	 */
	private $crypto;

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
		parent::__construct($logger);
		$this->crypto = $crypto;
		$this->userId = $UserId;
	}

	/**
	 * @param MailAccount $account
	 * @param $host
	 * @param $users
	 * @param $password
	 * @param bool $withHostPrefix
	 * @return bool
	 */
	public function test(MailAccount $account, $host, $users, $password,
		$withHostPrefix = false) {
		if (!is_array($users)) {
			$users = [$users];
		}

		// port 25 should be the last one to test
		$ports = [587, 465, 25];
		$protocols = ['ssl', 'tls', null];
		$hostPrefixes = [''];
		if ($withHostPrefix) {
			$hostPrefixes = ['', 'imap.'];
		}
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			if (gethostbyname($url) === $url) {
				continue;
			}
			foreach ($ports as $port) {
				if (!$this->canConnect($url, $port)) {
					continue;
				}
				foreach ($protocols as $protocol) {
					foreach ($users as $user) {
						try {
							$account->setOutboundHost($url);
							$account->setOutboundPort($port);
							$account->setOutboundUser($user);
							$password = $this->crypto->encrypt($password);
							$account->setOutboundPassword($password);
							$account->setOutboundSslMode($protocol);

							$a = new Account($account);
							$smtp = $a->createTransport();
							$smtp->getSMTPObject();

							$this->logger->info("Test-Account-Successful: $this->userId, $url, $port, $user, $protocol");

							return true;
						} catch (\Exception $e) {
							$error = $e->getMessage();
							$this->logger->info("Test-Account-Failed: $this->userId, $url, $port, $user, $protocol -> $error");
						}
					}
				}
			}
		}
		return false;
	}

}
