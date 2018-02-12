<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Service;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\SetupService;
use OCA\Mail\SMTP\SmtpClientFactory;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\Security\ICrypto;
use PHPUnit_Framework_MockObject_MockObject;

class SetupServiceTest extends TestCase {

	/** @var AutoConfig|PHPUnit_Framework_MockObject_MockObject */
	private $autoConfig;

	/** @var AccountService|PHPUnit_Framework_MockObject_MockObject */
	private $accountService;

	/** @var ICrypto|PHPUnit_Framework_MockObject_MockObject */
	private $crypto;

	/** @var SmtpClientFactory|PHPUnit_Framework_MockObject_MockObject */
	private $smtpClientFactory;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var SetupService */
	private $service;

	protected function setUp() {
		parent::setUp();

		$this->autoConfig = $this->createMock(AutoConfig::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->logger = $this->createMock(Logger::class);

		$this->service = new SetupService($this->autoConfig, $this->accountService, $this->crypto, $this->smtpClientFactory, $this->logger);
	}

	public function testCreateAutoConfiguredFailed() {
		$name = 'Jane Doe';
		$email = 'jane@doe.it';
		$password = '123456';
		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($email, $password, $name)
			->willReturn(null);
		$this->accountService->expects($this->never())
			->method('save');

		$actual = $this->service->createNewAutoConfiguredAccount($name, $email, $password);

		$this->assertNull($actual);
	}

	public function testCreateAutoConfigured() {
		$name = 'Jane Doe';
		$email = 'jane@doe.it';
		$password = '123456';
		$account = $this->createMock(MailAccount::class);
		$this->autoConfig->expects($this->once())
			->method('createAutoDetected')
			->with($email, $password, $name)
			->willReturn($account);
		$this->accountService->expects($this->once())
			->method('save')
			->with($account);
		$expected = new Account($account);

		$actual = $this->service->createNewAutoConfiguredAccount($name, $email, $password);

		$this->assertEquals($expected, $actual);
	}

}
