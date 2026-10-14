<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Protocol;

use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\JmapClientFactory;
use function hash;
use function json_encode;

/**
 * Long-running processes that handle many accounts must release each
 * account once done, or connections accumulate for the whole process.
 */
class ConnectionPool {
	/** @var array<int, array{fingerprint: string, client: JmapClient}> */
	private array $jmapClients = [];

	public function __construct(
		private JmapClientFactory $jmapClientFactory,
	) {
	}

	/**
	 * @throws ServiceException
	 */
	public function jmap(Account $account): JmapClient {
		$id = $account->getId();
		$fingerprint = $this->fingerprint($account);

		$entry = $this->jmapClients[$id] ?? null;
		if ($entry !== null && $entry['fingerprint'] === $fingerprint) {
			return $entry['client'];
		}

		$client = $this->jmapClientFactory->getClient($account);
		$this->jmapClients[$id] = [
			'fingerprint' => $fingerprint,
			'client' => $client,
		];
		return $client;
	}

	public function release(Account $account): void {
		unset($this->jmapClients[$account->getId()]);
	}

	private function fingerprint(Account $account): string {
		$mailAccount = $account->getMailAccount();
		return hash('sha256', json_encode([
			$mailAccount->getProtocol(),
			$mailAccount->getInboundHost(),
			$mailAccount->getInboundPort(),
			$mailAccount->getInboundSslMode(),
			$mailAccount->getPath(),
			$mailAccount->getInboundUser(),
			$mailAccount->getInboundPassword(),
			$mailAccount->getAuthMethod(),
		], JSON_THROW_ON_ERROR));
	}
}
