<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\RepairSyncJob;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;

class RepairSyncJobTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private RepairSyncJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->serviceMock = $this->createServiceMock(RepairSyncJob::class);
		$this->job = $this->serviceMock->getService();
		$this->serviceMock->getParameter('time')
			->method('getTime')
			->willReturn(10_000_000);
		$this->job->setArgument([
			'accountId' => 123,
		]);
	}

	private function mockAccount(string $protocol): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setUserId('user123');
		$mailAccount->setProtocol($protocol);
		$mailAccount->setInboundPassword('password');
		$account = new Account($mailAccount);
		$this->serviceMock->getParameter('accountService')
			->method('findById')
			->with(123)
			->willReturn($account);
		$this->serviceMock->getParameter('userManager')
			->method('get')
			->willReturn($this->createConfiguredMock(IUser::class, [
				'isEnabled' => true,
			]));
		return $account;
	}

	public function testReleasesClientsAfterRepair(): void {
		$account = $this->mockAccount(MailAccount::PROTOCOL_IMAP);
		$protocolFactory = $this->serviceMock->getParameter('protocolFactory');
		$protocolFactory->method('mailboxConnector')
			->willReturn($this->createMock(IMailboxConnector::class));
		$this->serviceMock->getParameter('mailboxMapper')
			->method('findAll')
			->willReturn([]);
		$this->serviceMock->getParameter('dispatcher')
			->expects(self::once())
			->method('dispatchTyped');
		$protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);

		$this->job->start($this->createMock(IJobList::class));
	}

	public function testReleasesClientsWhenMailboxSyncFails(): void {
		$account = $this->mockAccount(MailAccount::PROTOCOL_IMAP);
		$mailboxConnector = $this->createMock(IMailboxConnector::class);
		$mailboxConnector->method('syncAll')
			->willThrowException(new ServiceException('Could not connect to IMAP'));
		$protocolFactory = $this->serviceMock->getParameter('protocolFactory');
		$protocolFactory->method('mailboxConnector')
			->willReturn($mailboxConnector);
		$this->serviceMock->getParameter('dispatcher')
			->expects(self::never())
			->method('dispatchTyped');
		$protocolFactory->expects(self::once())
			->method('releaseClients')
			->with($account);

		$this->job->start($this->createMock(IJobList::class));
	}

	public function testSkipsJmapAccount(): void {
		$this->mockAccount(MailAccount::PROTOCOL_JMAP);
		$protocolFactory = $this->serviceMock->getParameter('protocolFactory');
		$protocolFactory->expects(self::never())
			->method('mailboxConnector');
		$protocolFactory->expects(self::never())
			->method('releaseClients');

		$this->job->start($this->createMock(IJobList::class));
	}
}
