<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SetupChecks;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;
use Throwable;

class MailConnectionPerformance implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private LoggerInterface $logger,
		private ProvisioningMapper $provisioningMapper,
		private MailAccountMapper $accountMapper,
		private IMAPClientFactory $clientFactory,
		private MicroTime $microtime,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Mail connection performance');
	}

	#[\Override]
	public function getCategory(): string {
		return 'mail';
	}

	#[\Override]
	public function run(): SetupResult {
		// retrieve unique imap hosts for provisionings and abort if none exists
		$hosts = $this->provisioningMapper->findUniqueImapHosts();
		if (empty($hosts)) {
			return SetupResult::success();
		}
		// retrieve random account ids for each host
		$accounts = [];
		foreach ($hosts as $host) {
			$accounts[$host] = $this->accountMapper->getRandomAccountIdsByImapHost($host);
		}
		// test accounts
		$tests = [];
		foreach ($accounts as $host => $collection) {
			foreach ($collection as $accountId) {
				$account = new Account($this->accountMapper->findById((int)$accountId));
				try {
					$client = $this->clientFactory->getClient($account);
				} catch (ServiceException $e) {
					$this->logger->warning('Error occurred while getting IMAP client for setup check: ' . $e->getMessage(), [
						'exception' => $e,
						'accountId' => $account->getId()
					]);
					continue;
				}
				try {
					$tStart = $this->microtime->getNumeric();
					// time login
					$client->login();
					$tLogin = $this->microtime->getNumeric();
					// time operation
					$list = $client->listMailboxes('*');
					$client->status(key($list));
					$tOperation = $this->microtime->getNumeric();

					$tests[$host][$accountId] = ['start' => $tStart, 'login' => $tLogin, 'operation' => $tOperation];
				} catch (Throwable $e) {
					$this->logger->warning('Error occurred while performing system check on mail account: ' . $account->getId());
				} finally {
					$client->close();
				}
			}
		}
		// calculate performance
		$performance = [];
		foreach ($tests as $host => $test) {
			$tLogin = 0;
			$tOperation = 0;
			foreach ($test as $entry) {
				[$start, $login, $operation] = array_values($entry);
				$tLogin += ($login - $start);
				$tOperation += ($operation - $login);
			}
			$performance[$host]['login'] = $tLogin / count($tests[$host]);
			$performance[$host]['operation'] = $tOperation / count($tests[$host]);
		}
		// display performance test outcome
		foreach ($performance as $host => $entry) {
			[$login, $operation] = array_values($entry);
			if ($login > 1) {
				return SetupResult::warning(
					$this->l10n->t('Slow mail service detected (%1$s) an attempt to connect to several accounts took an average of %2$s seconds per account', [$host, round($login, 3)])
				);
			}
			if ($operation > 1) {
				return SetupResult::warning(
					$this->l10n->t('Slow mail service detected (%1$s) an attempt to perform a mail box list operation on several accounts took an average of %2$s seconds per account', [$host, round($operation, 3)])
				);
			}
		}
		return SetupResult::success();
	}

}
