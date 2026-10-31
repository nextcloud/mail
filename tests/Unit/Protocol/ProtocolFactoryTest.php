<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Client as JmapClient;
use JmapClient\Session\Session;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\JMAP\JmapClientFactory;
use OCA\Mail\Protocol\ConnectionPool;
use OCA\Mail\Protocol\ProtocolFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class ProtocolFactoryTest extends TestCase {
	private JmapClientFactory&MockObject $jmapClientFactory;
	private ConnectionPool&MockObject $connectionPool;
	private ProtocolFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapClientFactory = $this->createMock(JmapClientFactory::class);
		$this->connectionPool = $this->createMock(ConnectionPool::class);

		$this->factory = new ProtocolFactory(
			$this->createMock(ContainerInterface::class),
			$this->createMock(IMAPClientFactory::class),
			$this->jmapClientFactory,
			$this->connectionPool,
		);
	}

	private function account(string $protocol): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setProtocol($protocol);
		return new Account($mailAccount);
	}

	public function testJmapClientComesFromPool(): void {
		$account = $this->account(MailAccount::PROTOCOL_JMAP);
		$client = $this->createMock(JmapClient::class);
		$this->connectionPool->expects(self::once())
			->method('jmap')
			->with($account)
			->willReturn($client);
		$this->jmapClientFactory->expects(self::never())
			->method('getClient');

		$result = $this->factory->jmapClient($account);

		self::assertSame($client, $result);
	}

	public function testJmapClientRejectsImapAccount(): void {
		$account = $this->account(MailAccount::PROTOCOL_IMAP);
		$this->connectionPool->expects(self::never())
			->method('jmap');
		$this->expectException(ServiceException::class);

		$this->factory->jmapClient($account);
	}

	public function testReleaseClients(): void {
		$account = $this->account(MailAccount::PROTOCOL_JMAP);
		$this->connectionPool->expects(self::once())
			->method('release')
			->with($account);

		$this->factory->releaseClients($account);
	}

	public function testTestConnectionUsesFreshJmapClient(): void {
		$account = $this->account(MailAccount::PROTOCOL_JMAP);
		$client = $this->createMock(JmapClient::class);
		$client->expects(self::once())
			->method('connect')
			->willReturn(new Session([]));
		$this->jmapClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$this->connectionPool->expects(self::never())
			->method('jmap');

		$this->factory->testConnection($account);
	}

	public function testTestConnectionPropagatesJmapFailure(): void {
		$account = $this->account(MailAccount::PROTOCOL_JMAP);
		$client = $this->createMock(JmapClient::class);
		$client->method('connect')
			->willThrowException(new \RuntimeException('Connection refused'));
		$this->jmapClientFactory->method('getClient')
			->willReturn($client);
		$this->expectException(\RuntimeException::class);

		$this->factory->testConnection($account);
	}
}
