<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\AutoConfig;

use Horde_Imap_Client_Exception;
use Horde_Mail_Exception;
use Horde_Mail_Transport_Smtphorde;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCP\Security\ICrypto;
use OpenCloud\Common\Log\Logger;

class IspDbConfigurationDetector {

	private $UserId;

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

	/**
	 * @param Logger $logger
	 * @param string $UserId
	 * @param ICrypto $crypto
	 * @param IspDb $ispDb
	 * @param ImapConnector $imapConnector
	 */
	public function __construct(Logger $logger, $UserId, ICrypto $crypto, IspDb $ispDb, ImapConnector $imapConnector) {
		$this->logger = $logger;
		$this->UserId = $UserId;
		$this->ispDb = $ispDb;
		$this->crypto = $crypto;
		$this->imapConnector = $imapConnector;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return MailAccount|null
	 */
	public function detectImapAndSmtp($email, $password, $name) {
		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list(, $host) = explode("@", $email);

		$ispdb = $this->ispDb->query($host);

		if (empty($ispdb)) {
			// Nothing to detect
			return null;
		}

		$account = $this->detectImap($ispdb, $email, $password, $name);

		// If IMAP detection failed we don't even try SMTP
		if (is_null($account)) {
			return null;
		}

		if ($this->detectSmtp($ispdb, $email, $password)) {
			return $account;
		}

		// IMAP detection succeeded, but SMTP failed
		return null;
	}

	/**
	 * @param array $ispdb
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return MailAccount|null
	 */
	private function detectImap(array $ispdb, $email, $password, $name) {
		if (!isset($ispdb['imap'])) {
			// Nothing to detect
			return null;
		}

		foreach ($ispdb['imap'] as $imap) {
			$account = $this->testImapConfiguration($imap, $email, $password, $name);
			if (!no_null($account)) {
				return $account;
			}
		}

		return null;
	}

	/**
	 * @param array $imap
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return MailAccount|null
	 */
	private function testImapConfiguration(array $imap, $email, $password, $name) {
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
			return $this->imapConnector->connect($email, $password, $name, $host, $port, $encryptionProtocol, $user);
		} catch (Horde_Imap_Client_Exception $e) {
			$error = $e->getMessage();
			$this->logger->info("Test-Account-Failed: $this->userId, $host, $port, $user, $encryptionProtocol -> $error");
		}

		return null;
	}

	/**
	 * @param array $ispdb
	 * @param MailAccount $account
	 * @param string $email
	 * @param string $password
	 * @return boolean
	 */
	private function detectSmtp(array $ispdb, MailAccount $account, $email, $password) {
		if (!isset($ispdb['smtp'])) {
			// Nothing to detect
			return null;
		}

		foreach ($ispdb['smtp'] as $smtp) {
			$detected = $this->testSmtpConfiguration($smtp, $account, $email, $password);
			if ($detected) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $smtp
	 * @param MailAccount $account
	 * @param string $email
	 * @param string $password
	 * @return boolean
	 */
	private function testSmtpConfiguration(array $smtp, MailAccount $account, $email, $password) {
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
			$account->setOutboundPassword($this->crypto->encrypt($password));
			$account->setOutboundUser($user);
			$account->setOutboundSslMode(strtolower($smtp['socketType']));

			$a = new Account($account);
			$transport = $a->createTransport();
			if ($transport instanceof Horde_Mail_Transport_Smtphorde) {
				$transport->getSMTPObject();
			}

			return true;
		} catch (Horde_Mail_Exception $ex) {
			$error = $ex->getMessage();
			$this->logger->info("Test-Account-Failed(smtp): $error");
		}
		return false;
	}

}
