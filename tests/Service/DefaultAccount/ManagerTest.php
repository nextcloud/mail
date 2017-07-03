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

namespace OCA\Mail\Tests\Service\DefaultAccount;

use OCA\Mail\Service\DefaultAccount\Manager;
use OCA\Mail\Service\Logger;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class ManagerTest extends PHPUnit_Framework_TestCase {

	/** @var IConfig|PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var IStore|PHPUnit_Framework_MockObject_MockObject */
	private $credentialStore;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var IUserSession|PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var ICrypto|PHPUnit_Framework_MockObject_MockObject */
	private $crypto;

	/** @var Manager|PHPUnit_Framework_MockObject_MockObject */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->credentialStore = $this->createMock(IStore::class);
		$this->logger = $this->createMock(Logger::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->crypto = $this->createMock(ICrypto::class);

		$this->manager = new Manager($this->config, $this->credentialStore, $this->logger, $this->userSession, $this->crypto);
	}

	public function testGetDefaultAccountWithoutConfigAvailble() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('app.mail.accounts.default'), $this->equalTo(null))
			->willReturn(null);

		$account = $this->manager->getDefaultAccount();

		$this->assertSame(null, $account);
	}

	public function testGetDefaultAccountWithCredentialsUnavailable() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('app.mail.accounts.default'), $this->equalTo(null))
			->willReturn([
				'email' => '%EMAIL%',
		]);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willThrowException(new CredentialsUnavailableException());

		$account = $this->manager->getDefaultAccount();

		$this->assertSame(null, $account);
	}

	public function testGetDefaultAccount() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('app.mail.accounts.default'), $this->equalTo(null))
			->willReturn([
				'email' => '%EMAIL%',
				'imapHost' => 'imap.domain.tld',
				'imapPort' => 993,
				'imapSslMode' => 'ssl',
				'smtpHost' => 'smtp.domain.tld',
				'smtpPort' => 465,
				'smtpSslMode' => 'tls',
		]);
		$credentials = $this->createMock(ICredentials::class);
		$user = $this->createMock(\OCP\IUser::class);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->credentialStore->expects($this->once())
			->method('getLoginCredentials')
			->willReturn($credentials);
		$credentials->expects($this->once())
			->method('getPassword')
			->willReturn('123456');
		$this->crypto->expects($this->once())
			->method('encrypt')
			->with($this->equalTo('123456'))
			->willReturn('encrypted');
		$expected = new \OCA\Mail\Db\MailAccount();
		$expected->setId(Manager::ACCOUNT_ID);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user123');
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user@domain.tld');
		$user->expects($this->once())
			->method('getDisplayName')
			->willReturn('Test User');
		$expected->setUserId('user123');
		$expected->setEmail('user@domain.tld');
		$expected->setName('Test User');
		$expected->setInboundUser('user@domain.tld');
		$expected->setInboundHost('imap.domain.tld');
		$expected->setInboundPort(993);
		$expected->setInboundSslMode('ssl');
		$expected->setInboundPassword('encrypted');
		$expected->setOutboundUser('user@domain.tld');
		$expected->setOutboundHost('smtp.domain.tld');
		$expected->setOutboundPort(465);
		$expected->setOutboundSslMode('tls');
		$expected->setOutboundPassword('encrypted');

		$account = $this->manager->getDefaultAccount();

		$this->assertEquals($expected, $account);
	}

}
