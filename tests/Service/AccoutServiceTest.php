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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DefaultAccount\Manager;
use OCP\IL10N;
use PHPUnit_Framework_MockObject_MockObject;

class AccountServiceTest extends TestCase {

	/** @var string */
	private $user = 'herbert';

	/** @var MailAccountMapper|PHPUnit_Framework_MockObject_MockObject */
	private $mapper;

	/** @var IL10N|PHPUnit_Framework_MockObject_MockObject */
	private $l10n;

	/** @var AccountService|PHPUnit_Framework_MockObject_MockObject */
	private $service;

	/** @var MailAccount|PHPUnit_Framework_MockObject_MockObject */
	private $account1;

	/** @var MailAccount|PHPUnit_Framework_MockObject_MockObject */
	private $account2;

	/** @var Manager|PHPUnit_Framework_MockObject_MockObject */
	private $defaultAccountManager;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->createMock(MailAccountMapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaultAccountManager = $this->createMock(Manager::class);
		$this->service = new AccountService($this->mapper, $this->l10n, $this->defaultAccountManager);

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
		$actual = $this->service->findByUserId($this->user);

		$this->assertEquals($expected, $actual);
	}

	public function testFind() {
		$accountId = 123;

		$this->mapper->expects($this->once())
			->method('find')
			->with($this->user, $accountId)
			->will($this->returnValue($this->account1));

		$expected = new Account($this->account1);
		$actual = $this->service->find($this->user, $accountId);

		$this->assertEquals($expected, $actual);
	}

	public function testFindNotFound() {
		// TODO: implement code + write tests
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

		$this->service->delete($this->user, $accountId);
	}

	public function testSave() {
		$account = new MailAccount();
		$expected = 42;

		$this->mapper->expects($this->once())
			->method('save')
			->with($account)
			->will($this->returnValue($expected));

		$actual = $this->service->save($account);

		$this->assertEquals($expected, $actual);
	}

}
