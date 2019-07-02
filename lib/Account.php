<?php

/**
 * @author Alexander Weidinger <alexwegoo@gmail.com>
 * @author Christian Nöding <christian@noeding-online.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <ChristophWurst@users.noreply.github.com>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Clement Wong <mail@clement.hk>
 * @author gouglhupf <dr.gouglhupf@gmail.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas I <thomas@oatr.be>
 * @author Thomas Mueller <thomas.mueller@tmit.eu>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail;

use Horde\ManageSieve;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use Horde_Mail_Rfc822_List;
use Horde_Mail_Transport;
use Horde_Mail_Transport_Smtphorde;
use JsonSerializable;
use OC;
use OCA\Mail\Cache\Cache;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\Message;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;

class Account implements JsonSerializable {

	/** @var MailAccount */
	private $account;

	/** @var Horde_Imap_Client_Socket */
	private $imapClient;

	/** @var ManageSieve */
	private $sieveClient;

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $memcacheFactory;

	/** @var Alias */
	private $alias;

	/**
	 * @param MailAccount $account
	 */
	public function __construct(MailAccount $account) {
		$this->account = $account;
		$this->crypto = OC::$server->getCrypto();
		$this->config = OC::$server->getConfig();
		$this->memcacheFactory = OC::$server->getMemcacheFactory();
	}

	public function getMailAccount() {
		return $this->account;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->account->getId();
	}

	/**
	 * @param Alias|null $alias
	 * @return void
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->alias ? $this->alias->getName() : $this->account->getName();
	}

	/**
	 * @return string
	 */
	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	/**
	 * @return Horde_Imap_Client_Socket
	 * @throws \Horde_Imap_Client_Exception
	 */
	public function getImapConnection() {
		if (is_null($this->imapClient)) {
			$host = $this->account->getInboundHost();
			$user = $this->account->getInboundUser();
			$password = $this->account->getInboundPassword();
			$password = $this->crypto->decrypt($password);
			$port = $this->account->getInboundPort();
			$ssl_mode = $this->convertSslMode($this->account->getInboundSslMode());

			$params = [
				'username' => $user,
				'password' => $password,
				'hostspec' => $host,
				'port' => $port,
				'secure' => $ssl_mode,
				'timeout' => (int) $this->config->getSystemValue('app.mail.imap.timeout', 20),
			];
			if ($this->config->getSystemValue('debug', false)) {
				$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_imap.log';
			}
			if ($this->config->getSystemValue('app.mail.server-side-cache.enabled', true)) {
				if ($this->memcacheFactory->isAvailable()) {
					$params['cache'] = [
						'backend' => new Cache(array(
							'cacheob' => $this->memcacheFactory
								->createDistributed(md5($this->getId() . $this->getEMailAddress()))
						))];
				}
			}
			$this->imapClient = new \Horde_Imap_Client_Socket($params);
			$this->imapClient->login();
		}
		return $this->imapClient;
	}

	/**
	 * @return ManageSieve
	 * @throws ManageSieve\Exception
	 */
	public function getSieveConnection()
	{
		if ($this->sieveClient === null) {
			$host = $this->account->getSieveHost();
			$user = $this->account->getSieveUser();
			$password = $this->account->getSievePassword();
			$password = $this->crypto->decrypt($password);
			$port = $this->account->getSievePort();
			$ssl_mode = $this->convertSslMode($this->account->getSieveSslMode());

			$params = [
				'host' => $host,
				'port' => $port,
				'user' => $user,
				'password' => $password,
				'secure' => $ssl_mode,
			];

			// TODO: configure sieve logging
			/*if ($this->config->getSystemValue('debug', false)) {
				$params['logger'] = $this->config->getSystemValue('datadirectory') . '/horde_sieve.log';
			}*/

			$this->sieveClient = new ManageSieve($params);

		}

		return $this->sieveClient;
	}

	/**
	 * @param string $folderId
	 * @return Mailbox
	 */
	public function getMailbox($folderId) {
		$conn = $this->getImapConnection();
		$parts = explode('/', $folderId);
		if (count($parts) > 1 && $parts[1] === 'FLAGGED') {
			$mailbox = new Horde_Imap_Client_Mailbox($parts[0]);
			return new SearchMailbox($conn, $mailbox, []);
		}
		$mailbox = new Horde_Imap_Client_Mailbox($folderId);
		return new Mailbox($conn, $mailbox, []);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->account->toJson();
	}

	/**
	 * Convert special security mode values into Horde parameters
	 *
	 * @param string $sslMode
	 * @return false|string
	 */
	protected function convertSslMode($sslMode) {
		switch ($sslMode) {
			case 'none':
				return false;
		}
		return $sslMode;
	}

	/**
	 * @return string|Horde_Mail_Rfc822_List
	 */
	public function getEmail() {
		return $this->account->getEmail();
	}

	public function testConnectivity(Horde_Mail_Transport $transport) {
		// connect to imap
		$this->getImapConnection();

		// connect to smtp
		if ($transport instanceof Horde_Mail_Transport_Smtphorde) {
			$transport->getSMTPObject();
		}

		// connect to sieve
		if (!is_null($this->account->getSieveHost())) {
			$this->getSieveConnection();
		}
	}

	/**
	 * Factory method for creating new messages
	 *
	 * @return IMessage
	 */
	public function newMessage() {
		return new Message();
	}

}
