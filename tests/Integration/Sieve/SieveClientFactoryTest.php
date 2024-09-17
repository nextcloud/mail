<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Integration\Sieve;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde\ManageSieve;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Sieve\SieveClientFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

class SieveClientFactoryTest extends TestCase {
	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var IConfig|MockObject */
	private $config;

	/** @var SieveClientFactory */
	private $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->config = $this->createMock(IConfig::class);

		$this->config->method('getSystemValue')
			->willReturnCallback(static function ($key, $default) {
				if ($key === 'app.mail.sieve.timeout') {
					return 5;
				}
				if ($key === 'debug') {
					return false;
				}
				return null;
			});

		$this->config->method('getSystemValueBool')
			->with('app.mail.verify-tls-peer', true)
			->willReturn(false);

		$this->factory = new SieveClientFactory($this->crypto, $this->config);
	}

	private function getTestAccount(): MailAccount {
		$mailAccount = new MailAccount();
		$mailAccount->setId(123);
		$mailAccount->setEmail('user@domain.tld');
		$mailAccount->setInboundHost('127.0.0.1');
		$mailAccount->setInboundPort(993);
		$mailAccount->setInboundSslMode('ssl');
		$mailAccount->setInboundUser('user@domain.tld');
		$mailAccount->setInboundPassword(Server::get(ICrypto::class)->encrypt('mypassword'));
		$mailAccount->setSieveHost('127.0.0.1');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveSslMode('');
		$mailAccount->setSieveUser('');
		$mailAccount->setSievePassword('');
		return $mailAccount;
	}

	public function testClientConnectivity() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);
		$this->assertInstanceOf(ManageSieve::class, $client);
	}

	public function testClientInstallScript() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);

		$client->installScript('test', '#test');
		$this->assertCount(1, $client->listScripts());

		$client->removeScript('test');
		$this->assertCount(0, $client->listScripts());
	}
}
