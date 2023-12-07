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

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OC\Memcache\Redis;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\ImapClientRateLimitingDecorator;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use function ltrim;

class IMAPClientFactoryTest extends TestCase {
	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var IConfig|MockObject */
	private $config;

	/** @var ICacheFactory|MockObject */
	private $cacheFactory;

	/** @var IMAPClientFactory */
	private $factory;
	private IEventDispatcher|MockObject $eventDispatcher;
	private ITimeFactory|MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = Server::get(ICacheFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->factory = new IMAPClientFactory(
			$this->crypto,
			$this->config,
			$this->cacheFactory,
			$this->eventDispatcher,
			$this->timeFactory,
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
		$mailAccount->setInboundPassword('encrypted');
		return new Account($mailAccount);
	}

	public function testGetClient() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);

		$this->assertInstanceOf(Horde_Imap_Client_Socket::class, $client);
	}

	public function testClientConnectivity() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);
		$client->login();
	}

	/**
	 * @group slow
	 */
	public function testRateLimiting(): void {
		$config = Server::get(IConfig::class);
		$cacheClass = $config->getSystemValueString('memcache.distributed');
		if (ltrim($cacheClass, '\\') !== Redis::class) {
			$this->markTestSkipped('Redis not available. Found ' . $cacheClass);
		}
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('notmypassword');

		$client = $this->factory->getClient($account);
		self::assertInstanceOf(ImapClientRateLimitingDecorator::class, $client);
		foreach ([1, 2, 3] as $attempts) {
			try {
				$client->login();
				$this->fail("Login #$attempts should cause an exception");
			} catch (Horde_Imap_Client_Exception $e) {
				if ($e->getCode() !== Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED) {
					throw $e;
				}

				// 🔥 This is fine 🔥
			}
		}
		$this->expectException(Horde_Imap_Client_Exception::class);
		$this->expectExceptionCode(Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED);
		$this->expectExceptionMessage('Too many auth attempts');
		$client->login();
	}
}
