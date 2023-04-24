<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\BackgroundJob\IJobList;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;

class AccountServiceTest extends TestCase {
	/** @var string */
	private $user = 'herbert';

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

	/** @var IJobList|MockObject */
	private $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->accountService = new AccountService(
			$this->mapper,
			$this->aliasesService,
			$this->jobList
		);

		$this->account1 = $this->createMock(MailAccount::class);
		$this->account2 = $this->createMock(MailAccount::class);
	}

	public function testFindByUserId() {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with($this->user)
			->will($this->returnValue([
				$this->account1,
				$this->account2,
			]));

		$expected = [
			new Account($this->account1),
			new Account($this->account2),
		];
		$actual = $this->accountService->findByUserId($this->user);

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
		$account = new MailAccount();

		$this->mapper->expects($this->once())
			->method('save')
			->with($account)
			->will($this->returnArgument(0));

		$actual = $this->accountService->save($account);

		$this->assertEquals($account, $actual);
	}

	public function testUpdateSignature() {
		$id = 3;
		$uid = 'ian';
		$signature = 'sig';
		$mailAccount = $this->createMock(MailAccount::class);
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
}
