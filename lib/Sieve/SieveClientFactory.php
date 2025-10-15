<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Sieve;

use Horde\ManageSieve;
use OCA\Mail\Account;
use OCP\IConfig;
use OCP\Security\ICrypto;

class SieveClientFactory {
	private ICrypto $crypto;
	private IConfig $config;
	private array $cache = [];

	public function __construct(ICrypto $crypto, IConfig $config) {
		$this->crypto = $crypto;
		$this->config = $config;
	}

	/**
	 * @throws ManageSieve\Exception
	 */
	public function getClient(Account $account): ManageSieve {
		if (!isset($this->cache[$account->getId()])) {
			$user = $account->getMailAccount()->getSieveUser();
			if (empty($user)) {
				$user = $account->getMailAccount()->getInboundUser();
			}
			$password = $account->getMailAccount()->getSievePassword();
			if (empty($password)) {
				$password = $account->getMailAccount()->getInboundPassword();
			}

			if ($account->getDebug() || $this->config->getSystemValueBool('app.mail.debug')) {
				$logFile = $this->config->getSystemValue('datadirectory') . '/mail-' . $account->getUserId() . '-' . $account->getId() . '-sieve.log';
			} else {
				$logFile = null;
			}

			$this->cache[$account->getId()] = $this->createClient(
				$account->getMailAccount()->getSieveHost(),
				$account->getMailAccount()->getSievePort(),
				$user,
				$this->crypto->decrypt($password),
				$account->getMailAccount()->getSieveSslMode(),
				$logFile,
			);
		}

		return $this->cache[$account->getId()];
	}

	/**
	 * @param string $sslMode possible values: '', 'none', 'ssl' or 'tls'
	 * @param ?string $logFile absolute path for logFile or null to disable logging
	 * @throws ManageSieve\Exception
	 */
	public function createClient(string $host, int $port, string $user, string $password, string $sslMode, ?string $logFile): ManageSieve {
		if (empty($sslMode)) {
			$sslMode = true;
		} elseif ($sslMode === 'none') {
			$sslMode = false;
		}

		$params = [
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'password' => $password,
			'secure' => $sslMode,
			'timeout' => $this->config->getSystemValueInt('app.mail.sieve.timeout', 5),
			'context' => [
				'ssl' => [
					'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),

				]
			],
		];

		if ($logFile !== null) {
			$params['logger'] = new SieveLogger($logFile);
		}

		return new ManageSieve($params);
	}
}
