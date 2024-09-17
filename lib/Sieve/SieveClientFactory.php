<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Sieve;

use Horde\ManageSieve;
use OCA\Mail\Db\MailAccount;
use OCP\IConfig;
use OCP\Security\ICrypto;

class SieveClientFactory {
	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	private $cache = [];

	/**
	 * @param ICrypto $crypto
	 * @param IConfig $config
	 */
	public function __construct(ICrypto $crypto, IConfig $config) {
		$this->crypto = $crypto;
		$this->config = $config;
	}

	/**
	 * @return ManageSieve
	 * @throws ManageSieve\Exception
	 */
	public function getClient(MailAccount $account): ManageSieve {
		if (!isset($this->cache[($account->getId())])) {
			$user = $account->getSieveUser();
			if (empty($user)) {
				$user = $account->getInboundUser();
			}
			$password = $account->getSievePassword();
			if (empty($password)) {
				$password = $account->getInboundPassword();
			}

			$this->cache[($account->getId())] = $this->createClient(
				$account->getSieveHost(),
				$account->getSievePort(),
				$user,
				$password,
				$account->getSieveSslMode()
			);
		}

		return $this->cache[($account->getId())];
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param string $user
	 * @param string $password
	 * @param string $sslMode
	 * @return ManageSieve
	 * @throws ManageSieve\Exception
	 */
	public function createClient(string $host, int $port, string $user, string $password, string $sslMode): ManageSieve {
		if (empty($sslMode)) {
			$sslMode = true;
		} elseif ($sslMode === 'none') {
			$sslMode = false;
		}

		$params = [
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'password' => $this->crypto->decrypt($password),
			'secure' => $sslMode,
			'timeout' => (int)$this->config->getSystemValue('app.mail.sieve.timeout', 5),
			'context' => [
				'ssl' => [
					'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),

				]
			],
		];

		if ($this->config->getSystemValue('debug', false)) {
			$params['logger'] = new SieveLogger($this->config->getSystemValue('datadirectory') . '/horde_sieve.log');
		}

		return new ManageSieve($params);
	}
}
