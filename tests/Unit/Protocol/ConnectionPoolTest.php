<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\JmapClientFactory;
use OCA\Mail\Protocol\ConnectionPool;
use PHPUnit\Framework\MockObject\MockObject;

class ConnectionPoolTest extends TestCase {
	private JmapClientFactory&MockObject $jmapClientFactory;
	private ConnectionPool $pool;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapClientFactory = $this->createMock(JmapClientFactory::class);

		$this->pool = new ConnectionPool($this->jmapClientFactory);
	}

	private function account(int $id): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId($id);
		$mailAccount->setProtocol(MailAccount::PROTOCOL_JMAP);
		$mailAccount->setInboundHost('jmap.example.com');
		$mailAccount->setInboundPort(443);
		$mailAccount->setInboundSslMode('yes');
		$mailAccount->setInboundUser('user@example.com');
		$mailAccount->setInboundPassword('encrypted');
		return new Account($mailAccount);
	}

	public function testReusesClientForSameAccount(): void {
		$account = $this->account(1);
		$client = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$first = $this->pool->jmap($account);
		$second = $this->pool->jmap($account);

		self::assertSame($client, $first);
		self::assertSame($client, $second);
	}

	public function testKeepsSeparateClientsPerAccount(): void {
		$accountA = $this->account(1);
		$accountB = $this->account(2);
		$clientA = $this->createMock(JmapClient::class);
		$clientB = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnCallback(fn (Account $account) => match ($account) {
				$accountA => $clientA,
				$accountB => $clientB,
			});

		$resultA = $this->pool->jmap($accountA);
		$resultB = $this->pool->jmap($accountB);

		self::assertSame($clientA, $resultA);
		self::assertSame($clientB, $resultB);
		self::assertSame($clientA, $this->pool->jmap($accountA));
	}

	public function testReplacesClientWhenCredentialsChange(): void {
		$account = $this->account(1);
		$oldClient = $this->createMock(JmapClient::class);
		$newClient = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls($oldClient, $newClient);
		$this->pool->jmap($account);

		$account->getMailAccount()->setInboundPassword('re-encrypted');
		$result = $this->pool->jmap($account);

		self::assertSame($newClient, $result);
	}

	public function testReplacesClientWhenHostChanges(): void {
		$account = $this->account(1);
		$oldClient = $this->createMock(JmapClient::class);
		$newClient = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls($oldClient, $newClient);
		$this->pool->jmap($account);

		$account->getMailAccount()->setInboundHost('other.example.com');
		$result = $this->pool->jmap($account);

		self::assertSame($newClient, $result);
	}

	public function testReleaseCreatesNewClientOnNextAccess(): void {
		$account = $this->account(1);
		$oldClient = $this->createMock(JmapClient::class);
		$newClient = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls($oldClient, $newClient);
		$this->pool->jmap($account);

		$this->pool->release($account);
		$result = $this->pool->jmap($account);

		self::assertSame($newClient, $result);
	}

	public function testReleaseKeepsOtherAccounts(): void {
		$accountA = $this->account(1);
		$accountB = $this->account(2);
		$clientB = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(3))
			->method('getClient')
			->willReturnCallback(fn (Account $account) => $account === $accountB
				? $clientB
				: $this->createMock(JmapClient::class));
		$this->pool->jmap($accountA);
		$this->pool->jmap($accountB);

		$this->pool->release($accountA);
		$this->pool->jmap($accountA);

		self::assertSame($clientB, $this->pool->jmap($accountB));
	}

	public function testDoesNotPoolFailedCreation(): void {
		$account = $this->account(1);
		$client = $this->createMock(JmapClient::class);
		$this->jmapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls(
				self::throwException(new ServiceException('No password set')),
				$client,
			);
		try {
			$this->pool->jmap($account);
			self::fail('Expected ServiceException');
		} catch (ServiceException) {
		}

		$result = $this->pool->jmap($account);

		self::assertSame($client, $result);
	}
}
