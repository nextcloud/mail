<?php

declare(strict_types=1);

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

namespace OCA\Mail\Tests\Unit\Service\Autoconfig;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use OCA\Mail\Service\AutoConfig\ConnectivityTester;
use OCA\Mail\Service\AutoConfig\ImapConnectivityTester;
use OCA\Mail\Service\AutoConfig\ImapConnector;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ImapConnectivityTesterTest extends TestCase {

	/** @var ImapConnector|MockObject */
	private $imapConnector;

	/** @var ConnectivityTester|MockObject */
	private $connectivityTester;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var ImapConnectivityTester */
	private $tester;

	protected function setUp(): void {
		parent::setUp();

		$this->imapConnector = $this->createMock(ImapConnector::class);
		$this->connectivityTester = $this->createMock(ConnectivityTester::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->tester = new ImapConnectivityTester(
			$this->imapConnector,
			$this->connectivityTester,
			'dave',
			$this->logger
		);
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
