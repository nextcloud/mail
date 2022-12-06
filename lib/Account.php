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

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use Horde_Mail_Transport;
use Horde_Mail_Transport_Smtphorde;
use JsonSerializable;
use OC;
use OCA\Mail\Cache\Cache;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\Message;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use ReturnTypeWillChange;

class Account implements JsonSerializable {
	/** @var MailAccount */
	private $account;

	/** @var Horde_Imap_Client_Socket */
	private $client;

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

	public function __destruct() {
		if ($this->client !== null) {
			$this->client->logout();
		}
	}

	public function getMailAccount(): MailAccount {
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
	 * @deprecated use \OCA\Mail\IMAP\IMAPClientFactory instead
	 * @return Horde_Imap_Client_Socket
	 *
	 * @throws ServiceException
	 */
	public function getImapConnection() {
		if ($this->client === null) {
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
				'context' => [
					'ssl' => [
						'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
						'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					],
				],
			];
			if ($this->config->getSystemValue('debug', false)) {
				$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_imap.log';
			}
			if ($this->config->getSystemValue('app.mail.server-side-cache.enabled', true)) {
				if ($this->memcacheFactory->isAvailable()) {
					$params['cache'] = [
						'backend' => new Cache([
							'cacheob' => $this->memcacheFactory
								->createDistributed(md5($this->getId() . $this->getEMailAddress()))
						])];
				}
			}
			$this->client = new \Horde_Imap_Client_Socket($params);
			try {
				$this->client->login();
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException(
					"Could not connect to IMAP host $host:$port: " . $e->getMessage(),
					$e->getCode(),
					$e
				);
			}
		}
		return $this->client;
	}

	/**
	 * @deprecated
	 * @param string $folderId
	 * @return Mailbox
	 *
	 * @throws ServiceException
	 */
	public function getMailbox($folderId) {
		return new Mailbox(
			$this->getImapConnection(),
			new Horde_Imap_Client_Mailbox($folderId)
		);
	}

	#[ReturnTypeWillChange]
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

	public function getEmail(): string {
		return $this->account->getEmail();
	}

	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->account->getUserId();
	}

	/**
	 * @deprecated
	 *
	 * @return void
	 *
	 * @throws ServiceException
	 */
	public function testConnectivity(Horde_Mail_Transport $transport): void {
		// connect to imap
		$this->getImapConnection();

		// connect to smtp
		if ($transport instanceof Horde_Mail_Transport_Smtphorde) {
			$transport->getSMTPObject();
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
