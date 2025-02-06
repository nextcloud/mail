<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SetupChecks;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;
use Throwable;

class MailConnectionPerformance implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private LoggerInterface $logger,
		private IDBConnection $db,
		private MailAccountMapper $accountMapper,
		private IMAPClientFactory $clientFactory,
		private FolderMapper $folderMapper,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Mail Connection Performance');
	}

	public function getCategory(): string {
		return 'mail';
	}

	public function run(): SetupResult {

		// retrieve distinct host(s)
		$query = $this->db->getQueryBuilder();
		$query->selectDistinct('imap_host')
			->from('mail_provisionings');
		$hosts = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
		// retrieve random accounts for each host
		$query = $this->db->getQueryBuilder();
		$query->select('id')
			->from('mail_accounts')
			->where($query->expr()->eq('inbound_host', $query->createParameter('host'), IQueryBuilder::PARAM_STR))
			->setMaxResults(1000);
		$accounts = [];
		foreach ($hosts as $host) {
			$query->setParameter('host', $host, IQueryBuilder::PARAM_STR);
			$ids = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
			// Pick 3 random accounts or any available
			if (count($ids) >= 3) {
				$rids = array_rand($ids, 3);
				$accounts[$host] = array_intersect_key($ids, array_flip($rids));
			} else {
				$accounts[$host] = $ids;
			}
		}
		// test accounts
		$tests = [];
		foreach ($accounts as $host => $collection) {
			foreach ($collection as $accountId) {
				$account = new Account($this->accountMapper->findById((int)$accountId));
				try {
					$client = $this->clientFactory->getClient($account);
					$tStart = microtime(true);

					// time login
					$client->login();
					$tLogin = microtime(true);
					// time operation
					$list = $client->listMailboxes('*');
					$status = $client->status(key($list));
					$tOperation = microtime(true);

					$tests[$host][$accountId] = ['start' => $tStart, 'login' => $tLogin, 'operation' => $tOperation];
				} catch (Throwable $e) {
					$this->logger->warning('Error occurred while performing system check on mail account');
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
