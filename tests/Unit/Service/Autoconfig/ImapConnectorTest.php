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
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AutoConfig\ImapConnector;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ImapConnectorTest extends TestCase {

	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IMAPClientFactory|MockObject */
	private $clientFactory;

	/** @var ImapConnector */
	private $connector;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);

		$this->connector = new ImapConnector(
			$this->crypto,
			$this->logger,
			$this->clientFactory,
			'christopher'
		);
	}

	public function testSuccessfulConnection() {
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$name = 'User';
		$host = 'localhost';
		$port = 993;
		$ssl = 'ssl';
		$user = 'user@domain.tld';

		$account = $this->connector->connect($email, $password, $name, $host, $port, $ssl, $user);

		$this->assertInstanceOf(MailAccount::class, $account);
	}

	/**
	 * The password is wrong
	 */
	public function testFailingConnection() {
		$email = 'user@domain.tld';
		$password = 'notmypassword';
		$name = 'User';
		$host = 'localhost';
		$port = 993;
		$ssl = 'ssl';
		$user = 'user@domain.tld';
		$this->expectException(Horde_Imap_Client_Exception::class);

		$client = $this->createMock(\Horde_Imap_Client_Socket::class);
		$client->method('login')
			->willThrowException(new Horde_Imap_Client_Exception());
		$this->clientFactory->method('getClient')
			->willReturn($client);

		$this->connector->connect($email, $password, $name, $host, $port, $ssl, $user);

		$this->fail('should not have been reached');
	}
}
