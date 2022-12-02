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

namespace OCA\Mail\Tests\Integration\IMAP;

use Horde_Imap_Client_Socket;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class IMAPClientFactoryTest extends TestCase {
	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var IConfig|MockObject */
	private $config;

	/** @var ICacheFactory|MockObject */
	private $cacheFactory;

	/** @var IMAPClientFactory */
	private $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->factory = new IMAPClientFactory(
			$this->crypto,
			$this->config,
			$this->cacheFactory,
			$this->eventDispatcher,
		);
	}

	/**
	 * @return Account
	 */
	private function getTestAccount() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword(OC::$server->getCrypto()->encrypt('mypassword'));
		return new Account($mailAccount);
	}

	public function testGetClient() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getMailAccount()->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);

		$this->assertInstanceOf(Horde_Imap_Client_Socket::class, $client);
	}

	public function testClientConnectivity() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getMailAccount()->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);
		$client->login();
	}
}
