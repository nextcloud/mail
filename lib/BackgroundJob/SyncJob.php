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

use Horde_Imap_Client_Exception;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;
use function defined;
use function method_exists;
use function sprintf;

class SyncJob extends TimedJob {
	private IUserManager $userManager;
	private AccountService $accountService;
	private ImapToDbSynchronizer $syncService;
	private MailboxSync $mailboxSync;
	private LoggerInterface $logger;
	private IJobList $jobList;

	public function __construct(ITimeFactory $time,
								IUserManager $userManager,
								AccountService $accountService,
								MailboxSync $mailboxSync,
								ImapToDbSynchronizer $syncService,
								LoggerInterface $logger,
								IJobList $jobList) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->syncService = $syncService;
		$this->mailboxSync = $mailboxSync;
		$this->logger = $logger;
		$this->jobList = $jobList;

		$this->setInterval(3600);
		/**
		 * @todo remove checks with 24+
		 */
		if (defined('\OCP\BackgroundJob\IJob::TIME_SENSITIVE') && method_exists($this, 'setTimeSensitivity')) {
			$this->setTimeSensitivity(self::TIME_SENSITIVE);
		}
	}

	/**
	 * @return void
	 */
	protected function run($argument) {
		$accountId = (int)$argument['accountId'];

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find account <' . $accountId . '> removing from jobs');
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$user = $this->userManager->get($account->getUserId());
		if ($user === null || !$user->isEnabled()) {
			$this->logger->debug(sprintf(
				'Account %d of user %s could not be found or was disabled, skipping background sync',
				$account->getId(),
				$account->getUserId()
			));
			return;
		}

		$dbAccount = $account->getMailAccount();
		if (!is_null($dbAccount->getProvisioningId()) && $dbAccount->getInboundPassword() === null) {
			$this->logger->info("Ignoring cron sync for provisioned account that has no password set yet");
			return;
		}

		try {
			$this->mailboxSync->sync($account, $this->logger, true);
			$this->syncService->syncAccount($account, $this->logger);
		} catch (IncompleteSyncException $e) {
			$this->logger->warning($e->getMessage(), [
				'exception' => $e,
			]);
		} catch (Throwable $e) {
			if ($e instanceof ServiceException
				&& $e->getPrevious() instanceof Horde_Imap_Client_Exception
				&& $e->getPrevious()->getCode() === Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED) {
				$this->logger->info('Cron mail sync authentication failed for account {accountId}', [
					'accountId' => $accountId,
					'exception' => $e,
				]);
			} else {
				$this->logger->error('Cron mail sync failed for account {accountId}', [
					'accountId' => $accountId,
					'exception' => $e,
				]);
			}
		}
	}
}
