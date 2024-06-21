<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use Horde\ManageSieve\Exception as ManagesieveException;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Sieve\NamedSieveScript;
use OCA\Mail\Sieve\SieveClientFactory;

class SieveService {
	public function __construct(
		private SieveClientFactory $sieveClientFactory,
		private AccountService $accountService,
	) {
	}

	/**
	 * @throws CouldNotConnectException
	 * @throws ClientException
	 * @throws ManagesieveException
	 */
	public function getActiveScript(string $userId, int $accountId): NamedSieveScript {
		$sieve = $this->getClient($userId, $accountId);

		$scriptName = $sieve->getActive();
		if ($scriptName === null) {
			$script = '';
		} else {
			$script = $sieve->getScript($scriptName);
		}

		// Sieve appends the script with a carriage return and line feed (\r\n) each time it's saved.
		// Strip those line feeds to avoid the accumulation of unnecessary white space.
		$script = rtrim($script, "\r\n");

		return new NamedSieveScript($scriptName, $script);
	}

	/**
	 * @throws ClientException
	 * @throws CouldNotConnectException
	 * @throws ManagesieveException
	 */
	public function updateActiveScript(string $userId, int $accountId, string $script): void {
		$sieve = $this->getClient($userId, $accountId);

		$scriptName = $sieve->getActive() ?? 'nextcloud';
		$sieve->installScript($scriptName, $script, true);
	}

	/**
	 * @throws ClientException
	 * @throws CouldNotConnectException
	 */
	private function getClient(string $userId, int $accountId): \Horde\ManageSieve {
		$account = $this->accountService->find($userId, $accountId);

		if (!$account->getMailAccount()->isSieveEnabled()) {
			throw new ClientException('ManageSieve is disabled');
		}

		try {
			$sieve = $this->sieveClientFactory->getClient($account);
		} catch (ManagesieveException $e) {
			throw new CouldNotConnectException($e, 'ManageSieve', $account->getMailAccount()->getSieveHost(), $account->getMailAccount()->getSievePort());
		}

		return $sieve;
	}
}
