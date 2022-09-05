<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\Mail\Tests\Integration\Sieve;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde\ManageSieve;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Sieve\SieveClientFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
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
		$mailAccount->setInboundPassword(OC::$server->get(ICrypto::class)->encrypt('mypassword'));
		$mailAccount->setSieveHost('127.0.0.1');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveSslMode('');
		$mailAccount->setSieveUser('');
		$mailAccount->setSievePassword('');
		return new Account($mailAccount);
	}

	public function testClientConnectivity() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getMailAccount()->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);
		$this->assertInstanceOf(ManageSieve::class, $client);
	}

	public function testClientInstallScript() {
		$account = $this->getTestAccount();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with($account->getMailAccount()->getInboundPassword())
			->willReturn('mypassword');

		$client = $this->factory->getClient($account);

		$client->installScript('test', '#test');
		$this->assertCount(1, $client->listScripts());

		$client->removeScript('test');
		$this->assertCount(0, $client->listScripts());
	}
}
