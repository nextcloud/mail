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

namespace OCA\Mail\Tests\Service\Autoconfig;

use Exception;
use Horde_Mail_Transport_Smtphorde;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\SmtpConnectivityTester;
use OCA\Mail\Service\Logger;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\Security\ICrypto;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class SmtpConnectivityTesterTest extends PHPUnit_Framework_TestCase {

	/** @var ConnectivityTester|PHPUnit_Framework_MockObject_MockObject */
	private $connectivityTester;

	/** @var ICrypto|PHPUnit_Framework_MockObject_MockObject */
	private $crypto;

	/** @var SmtpClientFactory|PHPUnit_Framework_MockObject_MockObject */
	private $clientFactory;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var SmtpConnectivityTester */
	private $tester;

	protected function setUp() {
		parent::setUp();

		$this->connectivityTester = $this->createMock(ConnectivityTester::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->clientFactory = $this->createMock(SmtpClientFactory::class);
		$this->logger = $this->createMock(Logger::class);
		$this->tester = new SmtpConnectivityTester($this->connectivityTester, $this->crypto, $this->clientFactory, $this->logger, 'dave');
	}

	public function testTest() {
		$account = $this->createMock(MailAccount::class);
		$host = 'gmail.com';
		$users = ['user'];
		$password = 'mypassword';
		$this->connectivityTester->expects($this->exactly(3))
			->method('canConnect')
			->willReturn(true);
		$transport = $this->createMock(Horde_Mail_Transport_Smtphorde::class);
		$this->clientFactory->expects($this->exactly(9))
			->method('create')
			->willReturn($transport);
		$transport->expects($this->exactly(9))
			->method('getSMTPObject')
			->willThrowException(new Exception());

		$result = $this->tester->test($account, $host, $users, $password);

		$this->assertFalse($result);
	}

}
