<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP;

use Exception;
use Horde_Imap_Client_Password_Xoauth2;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Cache\HordeCacheFactory;
use OCA\Mail\Events\BeforeImapClientCreated;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IMemcache;
use OCP\Security\ICrypto;
use function hash;
use function implode;
use function json_encode;

class IMAPClientFactory {
	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	private ITimeFactory $timeFactory;
	private HordeCacheFactory $hordeCacheFactory;

	public function __construct(ICrypto $crypto,
		IConfig $config,
		ICacheFactory $cacheFactory,
		IEventDispatcher $eventDispatcher,
		ITimeFactory $timeFactory,
		HordeCacheFactory $hordeCacheFactory) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->eventDispatcher = $eventDispatcher;
		$this->timeFactory = $timeFactory;
		$this->hordeCacheFactory = $hordeCacheFactory;
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
	 *
	 * @return Horde_Imap_Client_Socket
	 * @throws ServiceException
	 */
	public function getClient(Account $account, bool $useCache = true): Horde_Imap_Client_Socket {
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
			try {
				$decryptedAccessToken = $this->crypto->decrypt($account->getMailAccount()->getOauthAccessToken());
			} catch (Exception $e) {
				throw new ServiceException('Could not decrypt account password: ' . $e->getMessage(), 0, $e);
			}

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
		if ($useCache) {
			$params['cache'] = [
				'backend' => $this->hordeCacheFactory->newCache($account),
			];
		}
		if ($account->getDebug() || $this->config->getSystemValueBool('app.mail.debug')) {
			$fn = 'mail-' . $account->getUserId() . '-' . $account->getId() . '-imap.log';
			$params['debug'] = $this->config->getSystemValue('datadirectory') . '/' . $fn;
		}

		$client = new HordeImapClient($params);

		$rateLimitingCache = $this->cacheFactory->createDistributed('mail_imap_ratelimit');
		if ($rateLimitingCache instanceof IMemcache) {
			$client->enableRateLimiter($rateLimitingCache, $paramHash, $this->timeFactory);
		}

		return $client;
	}
}
