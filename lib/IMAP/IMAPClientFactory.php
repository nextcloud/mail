<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Cache_Backend_Null;
use Horde_Imap_Client_Password_Xoauth2;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Cache\Cache;
use OCA\Mail\Events\BeforeImapClientCreated;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IMemcache;
use OCP\Security\ICrypto;
use function hash;
use function implode;
use function json_encode;
use function md5;

class IMAPClientFactory {

	protected array $clientCache = [];

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	private ITimeFactory $timeFactory;

	public function __construct(ICrypto $crypto,
		IConfig $config,
		ICacheFactory $cacheFactory,
		IEventDispatcher $eventDispatcher,
		ITimeFactory $timeFactory) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->eventDispatcher = $eventDispatcher;
		$this->timeFactory = $timeFactory;
	}

	public function __destruct() {
		$this->clientCache;
        array_walk($this->clientCache, function($client) {
			try {
				$client->logout();
			} catch (\Throwable $e) {
				// Ignore any errors
			}
		});
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

		// generate connection signature
		$clientSignature = md5($account->getMailAccount()->getInboundHost() . $account->getMailAccount()->getInboundUser());
		// determine if connection is already cached and return cached object
		if (isset($this->clientCache[$clientSignature])) {
			return $this->clientCache[$clientSignature];
		}

		$this->eventDispatcher->dispatchTyped(
			new BeforeImapClientCreated($account)
		);
		$host = $account->getMailAccount()->getInboundHost();
		$user = $account->getMailAccount()->getInboundUser();
		$decryptedPassword = null;
		if ($account->getMailAccount()->getInboundPassword() !== null) {
			$decryptedPassword = $this->crypto->decrypt($account->getMailAccount()->getInboundPassword());
		}
		$port = $account->getMailAccount()->getInboundPort();
		$sslMode = $account->getMailAccount()->getInboundSslMode();
		if ($sslMode === 'none') {
			$sslMode = false;
		}

		$params = [
			'username' => $user,
			'password' => $decryptedPassword,
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
		if ($account->getMailAccount()->getAuthMethod() === 'xoauth2') {
			$decryptedAccessToken = $this->crypto->decrypt($account->getMailAccount()->getOauthAccessToken());

			$params['password'] = $decryptedAccessToken; // Not used, but Horde wants this
			$params['xoauth2_token'] = new Horde_Imap_Client_Password_Xoauth2(
				$account->getEmail(),
				$decryptedAccessToken,
			);
		}
		$paramHash = hash(
			'sha512',
			implode('-', [
				$this->config->getSystemValueString('secret'),
				$account->getId(),
				json_encode($params)
			]),
		);
		if ($useCache && $this->cacheFactory->isAvailable()) {
			$params['cache'] = [
				'backend' => new Cache([
					'cacheob' => $this->cacheFactory->createDistributed(md5((string)$account->getId())),
				])];
		} else {
			/**
			 * If we don't use a cache we use a null cache to trick Horde into
			 * using QRESYNC/CONDSTORE if they are available
			 * @see \Horde_Imap_Client_Socket::_loginTasks
			 */
			$params['cache'] = [
				'backend' => new Horde_Imap_Client_Cache_Backend_Null(),
			];
		}
		if ($this->config->getSystemValue('debug', false)) {
			$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde_imap.log';
		}

		$client = new HordeImapClient($params);

		$rateLimitingCache = $this->cacheFactory->createDistributed('mail_imap_ratelimit');
		if ($rateLimitingCache instanceof IMemcache) {
			$client->enableRateLimiter($rateLimitingCache, $paramHash, $this->timeFactory);
		}
		
		// cache client for reuse
		$this->clientCache[$clientSignature] = $client;

		return $this->clientCache[$clientSignature];
	}

	public function destroyClient(Account $account) {
		// generate connection signature
		$clientSignature = md5($account->getMailAccount()->getInboundHost() . $account->getMailAccount()->getInboundUser());
		// determine if connection is already cached and destroy it
		if (isset($this->clientCache[$clientSignature])) {
			$this->clientCache[$clientSignature]->logout();
			unset($this->clientCache[$clientSignature]);
		}
	}
}
