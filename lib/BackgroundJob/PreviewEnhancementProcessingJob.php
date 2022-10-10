<?php

declare(strict_types=1);
/**
 * @copyright Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use function sprintf;

class PreviewEnhancementProcessingJob extends TimedJob {
	private IUserManager $userManager;
	private AccountService $accountService;
	private LoggerInterface $logger;
	private IJobList $jobList;
	private PreprocessingService $preprocessingService;

	public function __construct(ITimeFactory $time,
								IUserManager $userManager,
								AccountService $accountService,
								PreprocessingService $preprocessingService,
								LoggerInterface $logger,
								IJobList $jobList) {
		parent::__construct($time);

		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->preprocessingService = $preprocessingService;

		$this->setInterval(3600);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @return void
	 */
	public function run($argument) {
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
				'Account %d of user %s could not be found or was disabled, skipping preprocessing of messages',
				$account->getId(),
				$account->getUserId()
			));
			return;
		}

		$dbAccount = $account->getMailAccount();
		if (!is_null($dbAccount->getProvisioningId()) && $dbAccount->getInboundPassword() === null) {
			$this->logger->info("Ignoring preprocessing job for provisioned account that has no password set yet");
			return;
		}

		$limitTimestamp = $this->time->getTime() - (60 * 60 * 24 * 14); // Two weeks into the past
		$this->preprocessingService->process($limitTimestamp, $account);
	}
}
