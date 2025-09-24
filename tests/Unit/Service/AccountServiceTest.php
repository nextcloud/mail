<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\QuotaJob;
use OCA\Mail\BackgroundJob\SyncJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;

class AccountServiceTest extends TestCase {
	private string $user = 'herbert';
	private string $user2 = 'user';

	/** @var MailAccountMapper|MockObject */
	private $mapper;

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var AccountService|MockObject */
	private $accountService;

	/** @var AliasesService|MockObject */
	private $aliasesService;

	/** @var MailAccount|MockObject */
	private $account1;

	/** @var MailAccount|MockObject */
	private $account2;

	/** @var MailAccount|MockObject */
	private $account3;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var Horde_Imap_Client_Socket|MockObject */
	private $client;

	private IConfig&MockObject $config;
	private ITimeFactory&MockObject $time;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->accountService = new AccountService(
			$this->mapper,
			$this->aliasesService,
			$this->jobList,
			$this->imapClientFactory,
			$this->config,
			$this->time,
		);

		$this->account1 = new MailAccount();
		$this->account2 = new MailAccount();
		$this->account3 = new MailAccount();
		$this->client = $this->createMock(Horde_Imap_Client_Socket::class);
	}

	public function testFindByUserId() {
		$this->mapper->expects(self::exactly(2))
			->method('findByUserId')
			->willReturnMap([
				[$this->user, [$this->account1, $this->account2]],
				[$this->user2, [$this->account3]],
			]);

		$expected = [
			new Account($this->account1),
			new Account($this->account2),
		];
		$actual = $this->accountService->findByUserId($this->user);
		$this->assertEquals($expected, $actual);

		$expected = [new Account($this->account3)];
		$actual = $this->accountService->findByUserId($this->user2);
		$this->assertEquals($expected, $actual);
	}

	public function testFind() {
		$accountId = 123;

		$this->mapper->expects($this->once())
			->method('find')
			->with($this->user, $accountId)
			->will($this->returnValue($this->account1));

		$expected = new Account($this->account1);
		$actual = $this->accountService->find($this->user, $accountId);

		$this->assertEquals($expected, $actual);
	}

	public function testFindById() {
		$accountId = 123;

		$this->mapper->expects($this->once())
			->method('findById')
			->with($accountId)
			->will($this->returnValue($this->account1));

		$expected = new Account($this->account1);
		$actual = $this->accountService->findById($accountId);

		$this->assertEquals($expected, $actual);
	}


	public function testDelete() {
		$accountId = 33;

		$this->mapper->expects($this->once())
			->method('find')
			->with($this->user, $accountId)
			->will($this->returnValue($this->account1));
		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->account1);

		$this->accountService->delete($this->user, $accountId);
	}

	public function testDeleteByAccountId() {
		$accountId = 33;

		$this->mapper->expects($this->once())
			->method('findById')
			->with($accountId)
			->will($this->returnValue($this->account1));
		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->account1);

		$this->accountService->deleteByAccountId($accountId);
	}

	public function testSave() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1000);
		$mailAccount->setUserId('user1');

		$this->mapper->expects($this->once())
			->method('save')
			->with($mailAccount)
			->will($this->returnArgument(0));

		$this->time->expects(self::exactly(2))
			->method('getTime')
			->willReturn(1755850409);

		$this->jobList->method('has')
			->willReturn(false);
		$this->jobList->expects($this->exactly(5))
			->method('scheduleAfter');

		$this->config->expects(self::once())
			->method('setUserValue')
			->with('user1', 'mail', 'ui-heartbeat', 1755850409);

		$actual = $this->accountService->save($mailAccount);

		$this->assertEquals($mailAccount, $actual);
	}

	public function testUpdateSignature() {
		$id = 3;
		$uid = 'ian';
		$signature = 'sig';
		$mailAccount = new MailAccount();
		$this->mapper->expects($this->once())
			->method('find')
			->with(
				$uid,
				$id
			)
			->willReturn($mailAccount);
		$this->mapper->expects($this->once())
			->method('save')
			->with($mailAccount);

		$this->accountService->updateSignature($id, $uid, $signature);
	}
	public function testAccountsFailedConnection() {
		$accountId = 1;
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willThrowException(new ClientException());
		$this->mapper->expects($this->once())
			->method('find')
			->with($this->user, $accountId)
			->willReturn($this->account1);
		$connected = $this->accountService->testAccountConnection($this->user, $accountId);
		$this->assertFalse($connected);
	}
	public function testAccountsSuccesfulConnection() {
		$accountId = 1;
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->willReturn($this->client);
		$this->client->expects($this->once())
			->method('close')
			->willReturn(null);
		$this->mapper->expects($this->once())
			->method('find')
			->with($this->user, $accountId)
			->willReturn($this->account1);
		$connected = $this->accountService->testAccountConnection($this->user, $accountId);
		$this->assertTrue($connected);
	}

	public function testScheduleBackgroundJobs(): void {
		$mailAccountId = 1000;
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(1755850409);
		$this->jobList->method('has')
			->willReturnCallback(function ($job) {
				return $job === SyncJob::class || $job === QuotaJob::class;
			});
		$this->jobList->expects($this->exactly(3))
			->method('scheduleAfter');

		$this->accountService->scheduleBackgroundJobs($mailAccountId);
	}
}
