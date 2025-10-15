<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\BackgroundJob\JobList;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\Db\MailAccount;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;

class SyncJobTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SyncJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(SyncJob::class);
		$this->job = $this->serviceMock->getService();

		// Make sure the job is actually run
		$this->serviceMock->getParameter('time')
			->method('getTime')
			->willReturn(500000);
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
			->with(SyncJob::class, ['accountId' => 123]);
		$this->serviceMock->getParameter('mailboxSync')
			->expects(self::never())
			->method('sync');
		$this->serviceMock->getParameter('syncService')
			->expects(self::never())
			->method('syncAccount');

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->setLastRun(0);
		$this->job->start($this->createMock(JobList::class));
	}

	public function testNoAuthentication(): void {
		$mailAccount = $this->createConfiguredMock(MailAccount::class, [
			'canAuthenticateImap' => false,
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
		$this->serviceMock->getParameter('logger')
			->expects(self::once())
			->method('debug')
			->with('No authentication on IMAP possible, skipping background sync job');
		$this->serviceMock->getParameter('userManager')
			->expects(self::never())
			->method('get');
		$this->serviceMock->getParameter('mailboxSync')
			->expects(self::never())
			->method('sync');
		$this->serviceMock->getParameter('syncService')
			->expects(self::never())
			->method('syncAccount');

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start($this->createMock(JobList::class));
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
			->with('Account 123 of user user123 could not be found or was disabled, skipping background sync');
		$this->serviceMock->getParameter('mailboxSync')
			->expects(self::never())
			->method('sync');
		$this->serviceMock->getParameter('syncService')
			->expects(self::never())
			->method('syncAccount');

		$this->job->setArgument([
			'accountId' => 123,
		]);
		$this->job->start($this->createMock(JobList::class));
	}
}
