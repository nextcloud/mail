<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use JmapClient\Authentication\Basic;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Creates authenticated JMAP client instances for a given account.
 */
class JmapClientFactory {

	public function __construct(
		private ICrypto $crypto,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Build an authenticated JMAP client for the given account.
	 *
	 * @throws ServiceException When credentials are missing or cannot be decrypted.
	 * @return JmapClient The configured JMAP client (not yet connected).
	 */
	public function getClient(Account $account): JmapClient {
		$mailAccount = $account->getMailAccount();

		$host = $mailAccount->getInboundHost();
		if ($host === null || $host === '') {
			throw new ServiceException('JMAP host is not configured for account ' . $account->getId());
		}

		$port = $mailAccount->getInboundPort();
		$secure = $mailAccount->getInboundSslMode() === 'yes';
		$path = $mailAccount->getPath() ?? '/.well-known/jmap';
		$user = $mailAccount->getInboundUser();
		$encryptedPassword = $mailAccount->getInboundPassword();

		if ($encryptedPassword === null) {
			throw new ServiceException('No password set for JMAP account ' . $account->getId());
		}

		try {
			$password = $this->crypto->decrypt($encryptedPassword);
		} catch (\Exception $e) {
			throw new ServiceException(
				'Could not decrypt password for JMAP account ' . $account->getId() . ': ' . $e->getMessage(),
				0,
				$e,
			);
		}

		$client = new JmapClient();
		$client->configureTransportMode($secure ? 'https' : 'http');
		$client->setHost($host . ':' . $port);
		if ($path !== '/.well-known/jmap') {
			$client->setDiscoveryPath($path);
		}
		$client->configureTransportVerification(
			$this->config->getSystemValueBool('app.mail.verify-tls-peer', true)
		);
		$client->setAuthentication(new Basic($user, $password));

		return $client;
	}
}
