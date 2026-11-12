<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\JmapClientFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class JmapClientFactoryTest extends TestCase {
	private ICrypto&MockObject $crypto;
	private IConfig&MockObject $config;
	private IClientService&MockObject $clientService;
	private JmapClientFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->config = $this->createMock(IConfig::class);
		$this->clientService = $this->createMock(IClientService::class);

		$this->factory = new JmapClientFactory(
			$this->crypto,
			$this->config,
			$this->clientService,
		);
	}

	private function account(array $overrides = []): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundHost($overrides['host'] ?? 'jmap.example.com');
		$mailAccount->setInboundPort($overrides['port'] ?? 443);
		$mailAccount->setInboundSslMode($overrides['ssl'] ?? 'yes');
		$mailAccount->setInboundUser($overrides['user'] ?? 'user@example.com');
		$mailAccount->setInboundPassword(array_key_exists('password', $overrides) ? $overrides['password'] : 'encrypted');
		if (array_key_exists('path', $overrides)) {
			$mailAccount->setPath($overrides['path']);
		}
		return new Account($mailAccount);
	}

	public function testBuildsConfiguredClient(): void {
		$this->crypto->method('decrypt')->with('encrypted')->willReturn('secret');
		$this->config->method('getSystemValueBool')->willReturn(true);
		$this->clientService->expects(self::once())
			->method('newClient')
			->willReturn($this->createMock(IClient::class));

		$client = $this->factory->getClient($this->account());

		self::assertInstanceOf(JmapClient::class, $client);
		self::assertSame('jmap.example.com:443', $client->getHost());
	}

	public function testThrowsWhenHostMissing(): void {
		$this->expectException(ServiceException::class);

		$this->factory->getClient($this->account(['host' => '']));
	}

	public function testThrowsWhenPasswordMissing(): void {
		$this->expectException(ServiceException::class);

		$this->factory->getClient($this->account(['password' => null]));
	}

	public function testWrapsDecryptionFailure(): void {
		$this->crypto->method('decrypt')->willThrowException(new \Exception('bad key'));

		$this->expectException(ServiceException::class);
		$this->factory->getClient($this->account());
	}
}
