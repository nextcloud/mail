<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OC\Memcache\Redis;
use OCA\Mail\Account;
use OCA\Mail\Cache\HordeCacheFactory;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\IMAP\HordeImapClient;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Tests\Integration\Framework\Caching;
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
	private HordeCacheFactory|MockObject $hordeCacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = Server::get(ICacheFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->hordeCacheFactory = $this->createMock(HordeCacheFactory::class);

		$this->factory = new IMAPClientFactory(
			$this->crypto,
			$this->config,
			$this->cacheFactory,
			$this->eventDispatcher,
			$this->timeFactory,
			$this->hordeCacheFactory,
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

	public function testGetClientDecryptionFailing(): void {
		$this->expectException(Exception::class);
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willThrowException(new Exception('Decryption does not throw a specific exception'));

		$this->factory->getClient($account);
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

		[$imapClientFactory, $cacheFactory] = Caching::getImapClientFactoryAndConfiguredCacheFactory($this->crypto);
		$this->assertInstanceOf(
			Redis::class,
			$cacheFactory->createDistributed(),
			'Distributed cache is not Redis',
		);

		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('notmypassword');

		$client = $imapClientFactory->getClient($account);
		self::assertInstanceOf(HordeImapClient::class, $client);
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
