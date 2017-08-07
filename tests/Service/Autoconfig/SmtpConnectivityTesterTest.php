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
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\SmtpConnectivityTester;
use OCA\Mail\Service\Logger;
use OCP\Security\ICrypto;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class SmtpConnectivityTesterTest extends PHPUnit_Framework_TestCase {

	/** @var ICrypto */
	private $crypto;

	/** @var ConnectivityTester|PHPUnit_Framework_MockObject_MockObject */
	private $connectivityTester;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var SmtpConnectivityTester */
	private $tester;

	protected function setUp() {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->connectivityTester = $this->createMock(ConnectivityTester::class);
		$this->logger = $this->createMock(Logger::class);
		$this->tester = $this->getMockBuilder(SmtpConnectivityTester::class)
			->setMethods(['testStmtpConnection'])
			->setConstructorArgs([$this->connectivityTester, $this->crypto, $this->logger, 'dave'])
			->getMock();
	}

	public function testTest() {
		$account = $this->createMock(MailAccount::class);
		$host = 'gmail.com';
		$users = ['user'];
		$password = 'mypassword';
		$this->connectivityTester->expects($this->exactly(3))
			->method('canConnect')
			->willReturn(true);
		$this->tester->expects($this->exactly(9))
			->method('testStmtpConnection')
			->willThrowException(new Exception());

		$result = $this->tester->test($account, $host, $users, $password);

		$this->assertFalse($result);
	}

}
