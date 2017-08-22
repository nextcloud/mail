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

use Horde_Imap_Client_Exception;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;

class ImapConnectivityTester {

	/** @var ImapConnector */
	private $imapConnector;

	/** @var ConnectivityTester */
	private $connectivityTester;

	/** @var string */
	private $userId;

	/** @var Logger */
	private $logger;

	/**
	 * @param ImapConnector $imapConnector
	 * @param ConnectivityTester $connectivityTester
	 * @param string $UserId
	 * @param Logger $logger
	 */
	public function __construct(ImapConnector $imapConnector,
		ConnectivityTester $connectivityTester, $UserId, Logger $logger) {
		$this->imapConnector = $imapConnector;
		$this->connectivityTester = $connectivityTester;
		$this->userId = $UserId;
		$this->logger = $logger;
	}

	/**
	 * @param $email
	 * @param $host
	 * @param $users
	 * @param $password
	 * @param $name
	 * @return MailAccount|null
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
				if (!$this->connectivityTester->canConnect($url, $port)) {
					continue;
				}
				foreach ($encryptionProtocols as $encryptionProtocol) {
					foreach ($users as $user) {
						try {
							return $this->imapConnector->connect($email, $password, $name, $host, $port, $encryptionProtocol, $user);
						} catch (Horde_Imap_Client_Exception $e) {
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
