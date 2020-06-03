<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 20178 Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\ILogger;
use OCP\Security\ICrypto;

class IspDbConfigurationDetector {

	/** @var string */
	private $UserId;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $userId;

	/** @var ICrypto */
	private $crypto;

	/** @var IspDb */
	private $ispDb;

	/** @var ImapConnector */
	private $imapConnector;

	/** @var SmtpClientFactory */
	private $smtpClientFactory;

	/**
	 * @param ILogger $logger
	 * @param string $UserId
	 * @param ICrypto $crypto
	 * @param IspDb $ispDb
	 * @param ImapConnector $imapConnector
	 * @param SmtpClientFactory $smtpClientFactory
	 */
	public function __construct(ILogger $logger,
								string $UserId = null,
								ICrypto $crypto,
								IspDb $ispDb,
								ImapConnector $imapConnector,
								SmtpClientFactory $smtpClientFactory) {
		$this->logger = $logger;
		$this->UserId = $UserId;
		$this->ispDb = $ispDb;
		$this->crypto = $crypto;
		$this->imapConnector = $imapConnector;
		$this->smtpClientFactory = $smtpClientFactory;
	}

	/**
	 * @param string $email
	 * @param string $password
	 * @param string $name
	 * @return MailAccount|null
	 */
	public function detectImapAndSmtp(string $email, string $password, string $name) {
		// splitting the email address into user and host part
		// TODO: use horde libs for email address parsing
		list(, $host) = explode("@", $email);

		$ispdb = $this->ispDb->query($host, $email);

		if (empty($ispdb)) {
			// Nothing to detect
			return null;
		}

		$account = $this->detectImap($ispdb, $email, $password, $name);

		// If IMAP detection failed we don't even try SMTP
		if (is_null($account)) {
			return null;
		}

		if ($this->detectSmtp($ispdb, $account, $email, $password)) {
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
	private function detectImap(array $ispdb, string $email, string $password, string $name) {
		if (!isset($ispdb['imap'])) {
			// Nothing to detect
			return null;
		}

		foreach ($ispdb['imap'] as $imap) {
			$account = $this->testImapConfiguration($imap, $email, $password, $name);
			if (!is_null($account)) {
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
	private function testImapConfiguration(array $imap, string $email, string $password, string $name) {
		$host = $imap['hostname'];
		$port = (int) $imap['port'];
		$encryptionProtocol = 'none';
		if ($imap['socketType'] === 'SSL') {
			$encryptionProtocol = 'ssl';
		}
		if ($imap['socketType'] === 'STARTTLS') {
			$encryptionProtocol = 'tls';
		}
		if ($imap['username'] === '%EMAILADDRESS%') {
			$user = $email;
		} elseif ($imap['username'] === '%EMAILLOCALPART%') {
			list($user,) = explode("@", $email);
		} elseif (empty($imap['username'])) {
			$this->logger->info("imap username is either an invalid placeholder or is empty");
			return null;
		} else {
			$user = $imap['username'];
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
	 *
	 * @return bool|null
	 */
	private function detectSmtp(array $ispdb, MailAccount $account, string $email, string $password): ?bool {
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
	private function testSmtpConfiguration(array $smtp, MailAccount $account, string $email, string $password) {
		try {
			if ($smtp['username'] === '%EMAILADDRESS%') {
				$user = $email;
			} elseif ($smtp['username'] === '%EMAILLOCALPART%') {
				list($user,) = explode("@", $email);
			} elseif (empty($smtp['username'])) {
				$this->logger->info("smtp username is either an unknown placeholder or is empty");
				return null;
			} else {
				$user = $smtp['username'];
			}

			$account->setOutboundHost($smtp['hostname']);
			$account->setOutboundPort($smtp['port']);
			$account->setOutboundPassword($this->crypto->encrypt($password));
			$account->setOutboundUser($user);
			$account->setOutboundSslMode(strtolower($smtp['socketType']));

			$a = new Account($account);
			$transport = $this->smtpClientFactory->create($a);
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
