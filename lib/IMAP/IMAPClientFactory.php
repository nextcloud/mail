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

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Cache\Cache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;

class IMAPClientFactory {

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $cacheFactory;

	/**
	 * @param ICrypto $crypto
	 * @param IConfig $config
	 * @param ICacheFactory $cacheFactory
	 */
	public function __construct(ICrypto $crypto, IConfig $config, ICacheFactory $cacheFactory) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * Get the connection object for the given account
	 *
	 * Connections are not closed until destruction, so the caller site is
	 * responsible to log out as soon as possible to keep the number of open
	 * (and stale) connections at a minimum.
	 *
	 * @param Account $account
	 * @param bool $useCache
	 * @return Horde_Imap_Client_Socket
	 */
	public function getClient(Account $account, bool $useCache = true): Horde_Imap_Client_Socket {
		$host = $account->getMailAccount()->getInboundHost();
		$user = $account->getMailAccount()->getInboundUser();
		$password = $account->getMailAccount()->getInboundPassword();
		$password = $this->crypto->decrypt($password);
		$port = $account->getMailAccount()->getInboundPort();
		$sslMode = $account->getMailAccount()->getInboundSslMode();
		if ($sslMode === 'none') {
			$sslMode = false;
		}

		$params = [
			'username' => $user,
			'password' => $password,
			'hostspec' => $host,
			'port' => $port,
			'secure' => $sslMode,
			'timeout' => (int)$this->config->getSystemValue('app.mail.imap.timeout', 5),
			'context' => [
				'ssl' => [
					'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
				],
			],
		];
		if ($useCache && $this->cacheFactory->isAvailable()) {
			$params['cache'] = [
				'backend' => new Cache([
					'cacheob' => $this->cacheFactory->createDistributed(md5((string)$account->getId())),
				])];
		}
		if ($this->config->getSystemValue('debug', false)) {
			$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_imap.log';
		}
		return new Horde_Imap_Client_Socket($params);
	}
}
