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

use Horde_Imap_Client_Exception;
use OC;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig\ImapConnector;
use OCA\Mail\Service\Logger;
use OCP\Security\ICrypto;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class ImapConnectorTest extends PHPUnit_Framework_TestCase {

	/** @var ICrypto */
	private $crypto;

	/** @var Logger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var ImapConnector */
	private $connector;

	protected function setUp() {
		parent::setUp();

		$this->crypto = OC::$server->getCrypto();
		$this->logger = $this->createMock(Logger::class);

		$this->connector = new ImapConnector($this->crypto, $this->logger, 'christopher');
	}

	public function testSuccessfulConnection() {
		$email = 'user@domain.tld';
		$password = 'mypassword';
		$name = 'User';
		$host = 'localhost';
		$port = '993';
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
		$port = '993';
		$ssl = 'ssl';
		$user = 'user@domain.tld';
		$this->expectException(Horde_Imap_Client_Exception::class);

		$this->connector->connect($email, $password, $name, $host, $port, $ssl, $user);

		$this->fail('should not have been reached');
	}

}
