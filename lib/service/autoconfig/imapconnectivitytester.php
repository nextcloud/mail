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
use OCA\Mail\Service\Logger;

class ImapConnectivityTester extends ConnectivityTester {

	/**
	 * @var ImapConnector
	 */
	private $imapConnector;

	/**
	 * @var string
	 */
	private $userId;

	public function __construct(ImapConnector $imapConnector, Logger $logger,
		$UserId) {
		parent::__construct($logger);
		$this->imapConnector = $imapConnector;
		$this->userId = $UserId;
	}

	/**
	 * @param $email
	 * @param $host
	 * @param $users
	 * @param $password
	 * @param $name
	 * @return null|MailAccount
	 */
	public function test($email, $host, $users, $password, $name) {
		if (!is_array($users)) {
			$users = [$users];
		}

		$ports = [143, 585, 993];
		$encryptionProtocols = ['ssl', 'tls', null];
		$hostPrefixes = ['', 'imap.'];
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			if (gethostbyname($url) === $url) {
				continue;
			}
			foreach ($ports as $port) {
				if (!$this->canConnect($url, $port)) {
					continue;
				}
				foreach ($encryptionProtocols as $encryptionProtocol) {
					foreach ($users as $user) {
						try {
							return $this->imapConnector->connect($email, $password, $name, $host,
									$port, $encryptionProtocol, $user);
						} catch (\Horde_Imap_Client_Exception $e) {
							$error = $e->getMessage();
							$this->logger->info("Test-Account-Failed: $this->userId, $url, $port, $user, $encryptionProtocol -> $error");
						}
					}
				}
			}
		}
		return null;
	}

}
