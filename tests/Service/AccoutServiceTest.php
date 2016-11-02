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

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use PHPUnit_Framework_TestCase;

class AccountServiceTest extends PHPUnit_Framework_TestCase {

	private $user = 'herbert';
	private $mapper;
	private $l10n;
	private $service;
	private $account1;
	private $account2;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->getMockBuilder('OCA\Mail\Db\MailAccountMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->service = new AccountService($this->mapper, $this->l10n);
		$this->account1 = $this->getMockBuilder('OCA\Mail\Db\MailAccount')
			->disableOriginalConstructor()
			->getMock();
		$this->account2 = $this->getMockBuilder('OCA\Mail\Db\MailAccount')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testFindByUserId() {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with($this->user)
			->will($this->returnValue([$this->account1]));

		$expected = [
			new Account($this->account1)
		];
		$actual = $this->service->findByUserId($this->user);

		$this->assertEquals($expected, $actual);
	}

	public function testFindByUserIdUnifiedInbox() {
		$this->mapper->expects($this->once())
			->method('findByUserId')
			->with($this->user)
			->will($this->returnValue([
					$this->account1,
					$this->account2,
		]));

		$expected = [
			null,
			new Account($this->account1),
			new Account($this->account2),
		];
		$actual = $this->service->findByUserId($this->user);

		$this->assertCount(3, $actual);
		$this->assertEquals($expected[1], $actual[1]);
		$this->assertEquals($expected[2], $actual[2]);
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

	public function testDeleteUnifiedInbox() {
		$accountId = -1;

		$this->mapper->expects($this->never())
			->method('find')
			->with($this->user, $accountId)
			->will($this->returnValue($this->account1));
		$this->mapper->expects($this->never())
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
