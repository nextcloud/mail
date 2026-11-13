<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Job;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreviewEnhancementProcessingJobTest extends TestCase {
	private ProtocolFactory&MockObject $protocolFactory;
	/** @var ITimeFactory|ITimeFactory&MockObject|MockObject */
	private $time;

	/** @var IUserManager|IUserManager&MockObject|MockObject */
	private $manager;

	/** @var AccountService|AccountService&MockObject|MockObject */
	private $accountService;

	/** @var PreprocessingService|PreprocessingService&MockObject|MockObject */
	private $preprocessingService;

	/** @var MockObject|LoggerInterface|LoggerInterface&MockObject */
	private $logger;

	/** @var IJobList|IJobList&MockObject|MockObject */
	private $jobList;
	private PreviewEnhancementProcessingJob $job;

	/** @var int[] */
	private static $argument;

	public function setUp(): void {
		parent::setUp();
		$this->time = $this->createMock(ITimeFactory::class);
		$this->manager = $this->createMock(IUserManager::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->preprocessingService = $this->createMock(PreprocessingService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->job = new PreviewEnhancementProcessingJob(
			$this->time,
			$this->manager,
			$this->accountService,
			$this->preprocessingService,
			$this->logger,
			$this->jobList,
			$this->protocolFactory,
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
		$this->protocolFactory->expects(self::never())
			->method('releaseClients');

		$this->job->run(self::$argument);
	}

	public function testNoUser(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundPassword('pass');
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user123');
		$account->method('getMailAccount')->willReturn($mailAccount);

		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willReturn($account);
		$this->manager->expects(self::once())
			->method('get');
		$this->logger->expects(self::once())
			->method('debug');

		$this->job->run(self::$argument);
	}

	public function testProvisionedNoPassword(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundPassword(null);
		$account = new Account($mailAccount);

		$this->accountService->expects(self::once())
			->method('findById')
			->with(self::$argument['accountId'])
			->willReturn($account);
		$this->manager->expects(self::never())
			->method('get');
		$this->logger->expects(self::once())
			->method('info');

		$this->job->run(self::$argument);
	}

	public function testProcessing(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundPassword('pass');
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user123');
		$account->method('getMailAccount')->willReturn($mailAccount);
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
		$this->protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);

		$this->job->run(self::$argument);
	}

	public function testProcessingFailureStillReleasesClients(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundPassword('pass');
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user123');
		$account->method('getMailAccount')->willReturn($mailAccount);
		$user = $this->createConfiguredMock(IUser::class, [
			'isEnabled' => true,
		]);
		$this->accountService->method('findById')
			->willReturn($account);
		$this->manager->method('get')
			->willReturn($user);
		$this->time->method('getTime')
			->willReturn(time());
		$this->preprocessingService->method('process')
			->willThrowException(new ServiceException('Could not connect to IMAP'));
		$this->protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);
		$this->expectException(ServiceException::class);

		$this->job->run(self::$argument);
	}
}
