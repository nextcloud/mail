<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Protocol;

use Horde_Imap_Client_Exception;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\HordeImapClient;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\JMAP\JmapClientFactory;
use Psr\Log\LoggerInterface;
use function hash;
use function json_encode;
use function register_shutdown_function;

/**
 * Long-running processes that handle many accounts must release each
 * account once done, or connections accumulate for the whole process.
 */
class ConnectionPool {
	/** @var array<int, array<string, array{fingerprint: string, client: HordeImapClient}>> */
	private array $imapClients = [];
	/** @var array<int, array{fingerprint: string, client: JmapClient}> */
	private array $jmapClients = [];
	private bool $shutdownRegistered = false;

	public function __construct(
		private IMAPClientFactory $imapClientFactory,
		private JmapClientFactory $jmapClientFactory,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws ServiceException
	 */
	public function imap(Account $account, bool $useCache = true): HordeImapClient {
		$id = $account->getId();
		$variant = $useCache ? 'cache' : 'nocache';

		$entry = $this->imapClients[$id][$variant] ?? null;
		if ($entry !== null && $entry['fingerprint'] === $this->fingerprint($account)) {
			if ($entry['client']->isConnectionLost()) {
				$entry['client']->logout();
			}
			return $entry['client'];
		}
		if ($entry !== null) {
			$this->logout($entry['client']);
		}

		$client = $this->imapClientFactory->getClient($account, $useCache);
		// Creating the client may refresh the account's OAuth token
		$this->imapClients[$id][$variant] = [
			'fingerprint' => $this->fingerprint($account),
			'client' => $client,
		];
		$this->registerShutdown();
		return $client;
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
		$id = $account->getId();
		foreach ($this->imapClients[$id] ?? [] as $entry) {
			$this->logout($entry['client']);
		}
		unset($this->imapClients[$id], $this->jmapClients[$id]);
	}

	public function releaseAll(): void {
		foreach ($this->imapClients as $entries) {
			foreach ($entries as $entry) {
				$this->logout($entry['client']);
			}
		}
		$this->imapClients = [];
		$this->jmapClients = [];
	}

	private function logout(HordeImapClient $client): void {
		try {
			$client->logout();
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->debug('Could not log out of IMAP connection: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
	}

	private function registerShutdown(): void {
		if ($this->shutdownRegistered) {
			return;
		}
		register_shutdown_function($this->releaseAll(...));
		$this->shutdownRegistered = true;
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
			$mailAccount->getOauthAccessToken(),
			$mailAccount->getDebug(),
		], JSON_THROW_ON_ERROR));
	}
}
