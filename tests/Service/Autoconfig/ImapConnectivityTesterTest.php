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

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\ImapConnectivityTester;
use OCA\Mail\Service\AutoConfig\ImapConnector;
use OCA\Mail\Service\Logger;
use OpenCloud\Common\Log\Logger as Logger2;
use PHPUnit_Framework_MockObject_MockObject;

class ImapConnectivityTesterTest extends TestCase {

	/** @var ImapConnector|PHPUnit_Framework_MockObject_MockObject */
	private $imapConnector;

	/** @var ConnectivityTester|PHPUnit_Framework_MockObject_MockObject */
	private $connectivityTester;

	/** @var Logger2|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var ImapConnectivityTester */
	private $tester;

	protected function setUp() {
		parent::setUp();

		$this->imapConnector = $this->createMock(ImapConnector::class);
		$this->connectivityTester = $this->createMock(ConnectivityTester::class);
		$this->logger = $this->createMock(Logger::class);
		$this->tester = new ImapConnectivityTester($this->imapConnector, $this->connectivityTester, 'dave', $this->logger);
	}

	public function testTest() {
		$email = 'user@domain.tld';
		$host = 'gmail.com';
		$users = ['user'];
		$password = 'mypassword';
		$name = 'User';
		$this->connectivityTester->expects($this->exactly(6))
			->method('canConnect')
			->willReturn(true);
		$this->imapConnector->expects($this->exactly(18))
			->method('connect')
			->with($email, $password, $name, $host, $this->anything(), $this->anything(), $users[0])
			->willThrowException(new Horde_Imap_Client_Exception());

		$result = $this->tester->test($email, $host, $users, $password, $name);

		$this->assertNull($result);
	}

}
