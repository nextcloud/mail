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
namespace OCA\Mail\Service\AutoConfig;

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
	 * @return \OCA\Mail\Db\MailAccount|null
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
