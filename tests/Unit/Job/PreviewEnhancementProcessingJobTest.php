<?php
/*
 * *
 *  * Mail App
 *  *
 *  * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *  *
 *  * @author Anna Larch <anna.larch@gmx.net>
 *  *
 *  * This library is free software; you can redistribute it and/or
 *  * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 *  * License as published by the Free Software Foundation; either
 *  * version 3 of the License, or any later version.
 *  *
 *  * This library is distributed in the hope that it will be useful,
 *  * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  *
 *  * You should have received a copy of the GNU Affero General Public
 *  * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *  *
 *
 */

namespace OCA\Mail\Tests\Unit\Job;

use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Psr\Log\LoggerInterface;

class PreviewEnhancementProcessingJobTest extends TestCase {
	/** @var ITimeFactory|ITimeFactory&MockObject|MockObject  */
	private $time;

	/** @var IUserManager|IUserManager&MockObject|MockObject  */
	private $manager;

	/** @var AccountService|AccountService&MockObject|MockObject  */
	private $accountService;

	/** @var PreprocessingService|PreprocessingService&MockObject|MockObject  */
	private $preprocessingService;

	/** @var MockObject|LoggerInterface|LoggerInterface&MockObject  */
	private $logger;

	/** @var IJobList|IJobList&MockObject|MockObject  */
	private $jobList;

	/** @var int[]  */
	private static $argument;

	public function setUp(): void {
		parent::setUp();
		$this->time = $this->createMock(ITimeFactory::class);
		$this->manager = $this->createMock(IUserManager::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->preprocessingService = $this->createMock(PreprocessingService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->job = new PreviewEnhancementProcessingJob(
			$this->time,
			$this->manager,
			$this->accountService,
			$this->preprocessingService,
			$this->logger,
			$this->jobList
		);

		self::$argument = ['accountId' => 1];
	}

	public function testNoAccount(): void {
		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willThrowException(new DoesNotExistException('Account does not exist'));
		$this->logger->expects(self::once())
			->method('debug');
		$this->jobList->expects(self::once())
			->method('remove');

		$this->job->run(self::$argument);
	}

	public function testNoUser(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId(1);
		$account = new Account($mailAccount);

		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willReturn($account);
		$this->manager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn(null);
		$this->logger->expects(self::once())
			->method('debug');

		$this->job->run(self::$argument);
	}

	public function testProvisionedNoPassword(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId(1);
		$mailAccount->setProvisioningId(1);
		$mailAccount->setInboundPassword(null);
		$account = new Account($mailAccount);
		$user = $this->createMock(IUser::class);
		$user->setEnabled();

		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willReturn($account);
		$this->manager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn($user);
		$user->expects(self::once())
			->method('isEnabled')
			->willReturn(true);
		$this->logger->expects(self::once())
			->method('info');

		$this->job->run(self::$argument);
	}

	public function testProcessing(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setUserId(1);
		$account = new Account($mailAccount);
		$time = time();
		$user = $this->createMock(IUser::class);
		$user->setEnabled();

		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willReturn($account);
		$this->manager->expects(self::once())
			->method('get')
			->with($account->getUserId())
			->willReturn($user);
		$user->expects(self::once())
			->method('isEnabled')
			->willReturn(true);
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn($time);
		$this->preprocessingService->expects(self::once())
			->method('process')
			->with(($time - (60 * 60 * 24 * 14)), $account);
		$this->logger->expects(self::never())
			->method('error');

		$this->job->run(self::$argument);
	}
}
