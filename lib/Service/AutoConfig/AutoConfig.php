<?php

/**
 * @author Bernhard Scheirle <bernhard+git@scheirle.de>
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
use Horde_Mail_Transport_Smtphorde;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Logger;
use OCP\Security\ICrypto;
use PEAR_Exception;

class AutoConfig {

	/** @var Logger */
	private $logger;

	/** @var string */
	private $userId;

	/** @var ICrypto */
	private $crypto;

	/** @var IspDb */
	private $ispDb;

	/** @var ImapConnector */
	private $imapConnector;

	/** @var ConfigurationDetector */
	private $configDetector;

	/**
	 * @param Logger $logger
	 * @param string $UserId
	 * @param ICrypto $crypto
	 * @param IspDb $ispDb
	 * @param ImapConnector $imapConnector
	 * @param ConfigurationDetector $configDetector
	 */
	public function __construct(Logger $logger, $UserId,
		ICrypto $crypto, IspDb $ispDb, ImapConnector $imapConnector,
		ConfigurationDetector $configDetector) {
		$this->logger = $logger;
		$this->userId = $UserId;
		$this->crypto = $crypto;
		$this->ispDb = $ispDb;
		$this->imapConnector = $imapConnector;
		$this->configDetector = $configDetector;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return null|MailAccount
	 */
	public function createAutoDetected($email, $password, $name) {
		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list(, $host) = explode("@", $email);

		$ispdb = $this->ispDb->query($host);
		if (!empty($ispdb)) {
			$account = null;
			if (isset($ispdb['imap'])) {
				foreach ($ispdb['imap'] as $imap) {
					$host = $imap['hostname'];
					$port = $imap['port'];
					$encryptionProtocol = null;
					if ($imap['socketType'] === 'SSL') {
						$encryptionProtocol = 'ssl';
					}
					if ($imap['socketType'] === 'STARTTLS') {
						$encryptionProtocol = 'tls';
					}
					if ($imap['username'] === '%EMAILADDRESS%') {
						$user = $email;
					} elseif ($imap['username'] === '%EMAILLOCALPART%') {
						list($user, ) = explode("@", $email);
					} else {
						$this->logger->info("Unknown username variable: " . $imap['username']);
						return null;
					}
					try {
						$account = $this->imapConnector->connect($email, $password, $name, $host,
							$port, $encryptionProtocol, $user);
						break;
					} catch (Horde_Imap_Client_Exception $e) {
						$error = $e->getMessage();
						$this->logger->info("Test-Account-Failed: $this->userId, $host, $port, $user, $encryptionProtocol -> $error");
					}
				}
			}
			if (!is_null($account)) {
				foreach ($ispdb['smtp'] as $smtp) {
					try {
						if ($smtp['username'] === '%EMAILADDRESS%') {
							$user = $email;
						} elseif ($smtp['username'] === '%EMAILLOCALPART%') {
							list($user, ) = explode("@", $email);
						} else {
							$this->logger->info("Unknown username variable: " . $smtp['username']);
							return null;
						}

						$account->setOutboundHost($smtp['hostname']);
						$account->setOutboundPort($smtp['port']);
						$password = $this->crypto->encrypt($password);
						$account->setOutboundPassword($password);
						$account->setOutboundUser($user);
						$account->setOutboundSslMode(strtolower($smtp['socketType']));

						$a = new Account($account);
						$smtp = $a->createTransport();
						if ($smtp instanceof Horde_Mail_Transport_Smtphorde) {
							$smtp->getSMTPObject();
						}

						break;
					} catch (PEAR_Exception $ex) {
						$error = $ex->getMessage();
						$this->logger->info("Test-Account-Failed(smtp): $error");
					}
				}
				return $account;
			}
		}

		return $this->configDetector->detectImapAndSmtp($email, $password, $name);
	}

}
