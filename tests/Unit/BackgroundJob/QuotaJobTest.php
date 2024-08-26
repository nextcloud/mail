<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\BackgroundJob\JobList;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\QuotaJob;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\Quota;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use OCP\Notification\INotification;

class QuotaJobTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SyncJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(QuotaJob::class);
		$this->job = $this->serviceMock->getService();

		// Make sure the job is actually run
		$this->serviceMock->getParameter('time')
			->method('getTime')
			->willReturn(604801); // job interval + 1

		// Set our common argument
		$this->job->setArgument([
			'accountId' => 123,
		]);
		// Set a fake ID
		$this->job->setId(99);
	}

	public function testAccountDoesntExist(): void {
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willThrowException(new DoesNotExistException(''));
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('debug')
			->with('Could not find account <123> removing from jobs');
		$this->serviceMock->getParameter('jobList')
			->expects(self::once())
			->method('remove')
			->with(QuotaJob::class, ['accountId' => 123]);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::never())
			->method('getQuota');

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->setLastRun(0);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}

	public function testUserDoesntExist(): void {
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => true,
		]);
		$account = $this->createMock(Account::class);
		$account->method('getId')->willReturn(123);
		$account->method('getUserId')->willReturn('user123');
		$account->method('getMailAccount')->willReturn($mailAccount);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($account);
		$user = $this->createMock(IUser::class);
		$this->serviceMock->getParameter('userManager')
			->expects(self::once())
			->method('get')
			->with('user123')
			->willReturn($user);
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('debug')
			->with('Account 123 of user user123 could not be found or was disabled, skipping quota query');
		$this->serviceMock->getParameter('mailManager')
			->expects(self::never())
			->method('getQuota');

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}

	public function testQuotaNoAuthentication(): void {
		$oldQuota = 10;
		$newQuota = 20;
		$quotaDTO = new Quota(20, 100);
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => false,
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 123,
			'getUserId' => 'user123',
			'getMailAccount' => $mailAccount,
		]);
		$user = $this->createConfiguredMock(IUser::class, [
			'isEnabled' => true,
		]);

		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($account);
		$account->expects(self::once())
			->method('getMailAccount')
			->willReturn($mailAccount);
		$mailAccount->expects(self::once())
			->method('canAuthenticateImap');
		$this->serviceMock->getParameter('userManager')
			->expects(self::never())
			->method('get');
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('debug');
		$this->serviceMock->getParameter('mailManager')
			->expects(self::never())
			->method('getQuota');
		$account->expects(self::never())
			->method('calculateAndSetQuotaPercentage')
			->with($quotaDTO);
		$account->expects(self::never())
			->method('getQuotaPercentage')
			->willReturn($newQuota);
		$this->serviceMock->getParameter('accountService')
			->expects(self::never())
			->method('update')
			->with($mailAccount);

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}

	public function testQuotaTooLow(): void {
		$oldQuota = 10;
		$newQuota = 20;
		$quotaDTO = new Quota(20, 100);
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => true,
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 123,
			'getUserId' => 'user123',
			'getMailAccount' => $mailAccount,
		]);
		$user = $this->createConfiguredMock(IUser::class, [
			'isEnabled' => true,
		]);

		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($account);
		$mailAccount->expects(self::once())
			->method('canAuthenticateImap');
		$this->serviceMock->getParameter('userManager')
			->expects(self::once())
			->method('get')
			->with('user123')
			->willReturn($user);
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('debug');
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getQuota')
			->willReturn($quotaDTO);
		$account->expects(self::once())
			->method('calculateAndSetQuotaPercentage')
			->with($quotaDTO);
		$account->expects(self::exactly(3))
			->method('getMailAccount')
			->willReturn($mailAccount);
		$account->expects(self::once())
			->method('getQuotaPercentage')
			->willReturn($newQuota);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('update')
			->with($mailAccount);

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}

	public function testQuotaWithNotification(): void {
		$oldQuota = 85;
		$newQuota = 95;
		$quotaDTO = new Quota(95, 100);
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => true,
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 123,
			'getUserId' => 'user123',
			'getMailAccount' => $mailAccount,
			'getEmail' => 'user123@test.com',
		]);
		$user = $this->createConfiguredMock(IUser::class, [
			'isEnabled' => true,
		]);
		$notification = $this->createMock(INotification::class);

		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($account);
		$this->serviceMock->getParameter('userManager')
			->expects(self::once())
			->method('get')
			->with('user123')
			->willReturn($user);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getQuota')
			->willReturn($quotaDTO);
		$account->expects(self::once())
			->method('calculateAndSetQuotaPercentage')
			->with($quotaDTO);
		$account->expects(self::exactly(3))
			->method('getMailAccount')
			->willReturn($mailAccount);
		$account->expects(self::once())
			->method('getQuotaPercentage')
			->willReturn($newQuota);
		$account->expects(self::exactly(2))
			->method('getUserId')
			->willReturn('user123');
		$account->expects(self::exactly(2))
			->method('getEmail')
			->willReturn('user123@test.com');
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('update')
			->with($mailAccount);
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('debug');
		$time = new \DateTime('now');
		$this->serviceMock->getParameter('time')
			->expects(self::once())
			->method('getDateTime')
			->willReturn($time);
		$this->serviceMock->getParameter('notificationManager')
			->expects(self::once())
			->method('createNotification')
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setApp')
			->with('mail')
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setUser')
			->with('user123')
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setObject')
			->with('quota', 123)
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setSubject')
			->with('quota_depleted', [
				'id' => 123,
				'account_email' => 'user123@test.com'
			])
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setDateTime')
			->with($time)
			->willReturn($notification);
		$notification->expects(self::once())
			->method('setMessage')
			->with('percentage', [
				'id' => 123,
				'quota_percentage' => $newQuota,
			])
			->willReturn($notification);
		$this->serviceMock->getParameter('notificationManager')
			->expects(self::once())
			->method('notify')
			->with($notification);

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}

	public function testQuotaZero(): void {
		$oldQuota = 0;
		$newQuota = 0;
		$quotaDTO = new Quota(0, 0);
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => true,
		]);
		$account = $this->createConfiguredMock(Account::class, [
			'getId' => 123,
			'getUserId' => 'user123',
			'getMailAccount' => $mailAccount,
		]);
		$user = $this->createConfiguredMock(IUser::class, [
			'isEnabled' => true,
		]);

		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('findById')
			->with(123)
			->willReturn($account);
		$this->serviceMock->getParameter('userManager')
			->expects(self::once())
			->method('get')
			->with('user123')
			->willReturn($user);
		$this->serviceMock->getParameter('logger')
			->expects(self::never())
			->method('debug');
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getQuota')
			->willReturn($quotaDTO);
		$account->expects(self::once())
			->method('calculateAndSetQuotaPercentage')
			->with($quotaDTO);
		$account->expects(self::exactly(3))
			->method('getMailAccount')
			->willReturn($mailAccount);
		$account->expects(self::once())
			->method('getQuotaPercentage')
			->willReturn($newQuota);
		$this->serviceMock->getParameter('accountService')
			->expects(self::once())
			->method('update')
			->with($mailAccount);

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start(
			$this->createMock(JobList::class),
		);
	}
}
