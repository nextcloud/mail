<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Smtp;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Mail_Transport_Smtphorde;
use Horde_Smtp_Password_Xoauth2;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\HostNameFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class SmtpClientFactoryTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var HostNameFactory|MockObject */
	private $hostNameFactory;

	/** @var SmtpClientFactory */
	private $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->hostNameFactory = $this->createMock(HostNameFactory::class);

		$this->factory = new SmtpClientFactory($this->config, $this->crypto, $this->hostNameFactory);
	}

	public function testSmtpTransport() {
		$mailAccount = new MailAccount([
			'smtpHost' => 'smtp.domain.tld',
			'smtpPort' => 25,
			'smtpSslMode' => 'none',
			'smtpUser' => 'user@domain.tld',
			'smtpPassword' => 'obenc',
		]);
		$account = new Account($mailAccount);
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['app.mail.transport', 'smtp', 'smtp'],
				['app.mail.smtp.timeout', 20, 2],
			]);
		$this->config->expects($this->any())
			->method('getSystemValueBool')
			->willReturnMap([
				['app.mail.verify-tls-peer', true, true],
				['app.mail.debug', false, false],
			]);
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('obenc')
			->willReturn('pass123');
		$this->hostNameFactory->expects($this->once())
			->method('getHostName')
			->willReturn('cloud.example.com');
		$expected = new Horde_Mail_Transport_Smtphorde([
			'host' => 'smtp.domain.tld',
			'password' => 'pass123',
			'port' => 25,
			'username' => 'user@domain.tld',
			'secure' => false,
			'timeout' => 2,
			'localhost' => 'cloud.example.com',
			'context' => [
				'ssl' => [
					'verify_peer' => true,
					'verify_peer_name' => true,
				],
			],
		]);

		$transport = $this->factory->create($account);

		$this->assertNotNull($transport);
		$this->assertInstanceOf(Horde_Mail_Transport_Smtphorde::class, $transport);
		$this->assertEquals($expected, $transport);
	}

	public function dataXOAuth2Identity(): \Iterator {
		yield 'configured SMTP user' => ['user@domain.tld', 'user@domain.tld'];
		yield 'empty SMTP user' => ['', 'shared@domain.tld'];
		yield 'no SMTP user' => [null, 'shared@domain.tld'];
	}

	/**
	 * @dataProvider dataXOAuth2Identity
	 */
	public function testSmtpTransportWithXOAuth2(?string $smtpUser, string $expectedIdentity): void {
		$mailAccount = new MailAccount([
			'emailAddress' => 'shared@domain.tld',
			'smtpHost' => 'smtp.domain.tld',
			'smtpPort' => 25,
			'smtpSslMode' => 'none',
		]);
		$mailAccount->setOutboundUser($smtpUser);
		$mailAccount->setAuthMethod('xoauth2');
		$mailAccount->setOauthAccessToken('enctoken');
		$account = new Account($mailAccount);
		$this->mockConfig();
		$this->crypto->expects($this->once())
			->method('decrypt')
			->with('enctoken')
			->willReturn('token123');
		$this->hostNameFactory->expects($this->once())
			->method('getHostName')
			->willReturn('cloud.example.com');
		$expected = new Horde_Mail_Transport_Smtphorde([
			'host' => 'smtp.domain.tld',
			'password' => 'token123',
			'port' => 25,
			'username' => $smtpUser,
			'secure' => false,
			'timeout' => 2,
			'localhost' => 'cloud.example.com',
			'context' => [
				'ssl' => [
					'verify_peer' => true,
					'verify_peer_name' => true,
				],
			],
			'xoauth2_token' => new Horde_Smtp_Password_Xoauth2($expectedIdentity, 'token123'),
		]);

		$transport = $this->factory->create($account);

		$this->assertEquals($expected, $transport);
	}

	private function mockConfig(): void {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->willReturnMap([
				['app.mail.transport', 'smtp', 'smtp'],
				['app.mail.smtp.timeout', 20, 2],
			]);
		$this->config->expects($this->any())
			->method('getSystemValueBool')
			->willReturnMap([
				['app.mail.verify-tls-peer', true, true],
				['app.mail.debug', false, false],
			]);
	}
}
