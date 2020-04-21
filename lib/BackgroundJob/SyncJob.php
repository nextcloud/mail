<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\ILogger;
use Throwable;

class SyncJob extends TimedJob {

	/** @var AccountService */
	private $accountService;

	/** @var ImapToDbSynchronizer */
	private $syncService;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var ILogger */
	private $logger;

	/** @var IJobList */
	private $jobList;

	public function __construct(ITimeFactory $time,
								AccountService $accountService,
								MailboxSync $mailboxSync,
								ImapToDbSynchronizer $syncService,
								ILogger $logger,
								IJobList $jobList) {
		parent::__construct($time);

		$this->accountService = $accountService;
		$this->syncService = $syncService;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;

		$this->setInterval(3600);
		$this->jobList = $jobList;
	}

	protected function run($argument) {
		$accountId = (int)$argument['accountId'];

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$dbAccount = $account->getMailAccount();
		if ($dbAccount->getProvisioned() && $dbAccount->getInboundPassword() === null) {
			$this->logger->info("Ignoring cron sync for provisioned account that has no password set yet");
			return;
		}

		try {
			$this->mailboxSync->sync($account, true);
			$this->syncService->syncAccount($account);
		} catch (IncompleteSyncException $e) {
			$this->logger->logException($e, [
				'level' => ILogger::WARN,
			]);
		} catch (Throwable $e) {
			$this->logger->logException($e, [
				'message' => 'Cron mail sync failed: ' . $e->getMessage(),
			]);
		}
	}
}
