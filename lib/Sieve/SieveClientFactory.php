<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
	 * @param Account $account
	 * @return ManageSieve
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

			$this->cache[$account->getId()] = $this->createClient(
				$account->getMailAccount()->getSieveHost(),
				$account->getMailAccount()->getSievePort(),
				$user,
				$password,
				$account->getMailAccount()->getSieveSslMode()
			);
		}

		return $this->cache[$account->getId()];
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
