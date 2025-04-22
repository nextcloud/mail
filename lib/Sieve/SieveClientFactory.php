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

			$sslMode = $account->getMailAccount()->getSieveSslMode();
			if (empty($sslMode)) {
				$sslMode = true;
			} elseif ($sslMode === 'none') {
				$sslMode = false;
			}

			$params = [
				'host' => $account->getMailAccount()->getSieveHost(),
				'port' => $account->getMailAccount()->getSievePort(),
				'user' => $user,
				'password' => $this->crypto->decrypt($password),
				'secure' => $sslMode,
				'timeout' => $this->config->getSystemValueInt('app.mail.sieve.timeout', 5),
				'context' => [
					'ssl' => [
						'verify_peer' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
						'verify_peer_name' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
					]
				],
			];

			if ($account->getDebug() || $this->config->getSystemValueBool('app.mail.debug')) {
				$fn = 'mail-' . $account->getUserId() . '-' . $account->getId() . '-sieve.log';
				$params['logger'] = new SieveLogger($this->config->getSystemValue('datadirectory') . '/' . $fn);
			}

			$this->cache[$account->getId()] = new ManageSieve($params);
		}

		return $this->cache[$account->getId()];
	}

}
