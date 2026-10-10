<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Protocol;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use JmapClient\Client as JmapClient;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\HordeImapClient;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\JMAP\JmapClientFactory;
use OCA\Mail\Protocol\ConnectionPool;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ConnectionPoolTest extends TestCase {
	private IMAPClientFactory&MockObject $imapClientFactory;
	private JmapClientFactory&MockObject $jmapClientFactory;
	private ConnectionPool $pool;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->jmapClientFactory = $this->createMock(JmapClientFactory::class);

		$this->pool = new ConnectionPool(
			$this->imapClientFactory,
			$this->jmapClientFactory,
			$this->createMock(LoggerInterface::class),
		);
	}

	private function account(int $id, string $protocol = MailAccount::PROTOCOL_JMAP): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setId($id);
		$mailAccount->setProtocol($protocol);
		$mailAccount->setInboundHost('mail.example.com');
		$mailAccount->setInboundPort(443);
		$mailAccount->setInboundSslMode('yes');
		$mailAccount->setInboundUser('user@example.com');
		$mailAccount->setInboundPassword('encrypted');
		return new Account($mailAccount);
	}

	private function imapAccount(int $id): Account {
		return $this->account($id, MailAccount::PROTOCOL_IMAP);
	}

	/**
	 * Counts logouts without an invocation matcher, because clients left in
	 * the pool are logged out again by its shutdown function.
	 */
	private function countLogouts(HordeImapClient&MockObject $client): \stdClass {
		$counter = new \stdClass();
		$counter->count = 0;
		$client->method('logout')
			->willReturnCallback(function () use ($counter): void {
				$counter->count++;
			});
		return $counter;
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

	public function testReusesImapClientForSameAccount(): void {
		$account = $this->imapAccount(1);
		$client = $this->createMock(HordeImapClient::class);
		$logouts = $this->countLogouts($client);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->with($account, true)
			->willReturn($client);

		$first = $this->pool->imap($account);
		$second = $this->pool->imap($account);

		self::assertSame($client, $first);
		self::assertSame($client, $second);
		self::assertSame(0, $logouts->count);
	}

	public function testKeepsCachedAndUncachedImapClientsApart(): void {
		$account = $this->imapAccount(1);
		$cached = $this->createMock(HordeImapClient::class);
		$uncached = $this->createMock(HordeImapClient::class);
		$this->imapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnCallback(fn (Account $account, bool $useCache) => $useCache ? $cached : $uncached);

		$resultCached = $this->pool->imap($account);
		$resultUncached = $this->pool->imap($account, false);

		self::assertSame($cached, $resultCached);
		self::assertSame($uncached, $resultUncached);
		self::assertSame($cached, $this->pool->imap($account));
		self::assertSame($uncached, $this->pool->imap($account, false));
	}

	public function testReplacesImapClientAndLogsOutOldOneWhenCredentialsChange(): void {
		$account = $this->imapAccount(1);
		$oldClient = $this->createMock(HordeImapClient::class);
		$oldClient->expects(self::once())
			->method('logout');
		$newClient = $this->createMock(HordeImapClient::class);
		$this->imapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls($oldClient, $newClient);
		$this->pool->imap($account);

		$account->getMailAccount()->setOauthAccessToken('refreshed');
		$result = $this->pool->imap($account);

		self::assertSame($newClient, $result);
	}

	public function testKeepsImapClientWhenCreationRefreshedTheToken(): void {
		$account = $this->imapAccount(1);
		$client = $this->createMock(HordeImapClient::class);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturnCallback(function (Account $account) use ($client) {
				$account->getMailAccount()->setOauthAccessToken('refreshed');
				return $client;
			});
		$this->pool->imap($account);

		$result = $this->pool->imap($account);

		self::assertSame($client, $result);
	}

	public function testResetsLostImapConnectionOnReuse(): void {
		$account = $this->imapAccount(1);
		$client = $this->createMock(HordeImapClient::class);
		$client->method('isConnectionLost')
			->willReturn(true);
		$logouts = $this->countLogouts($client);
		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($client);
		$this->pool->imap($account);

		$result = $this->pool->imap($account);

		self::assertSame($client, $result);
		self::assertSame(1, $logouts->count);
	}

	public function testReleaseLogsOutAllImapClientsOfAccount(): void {
		$account = $this->imapAccount(1);
		$cached = $this->createMock(HordeImapClient::class);
		$cached->expects(self::once())
			->method('logout');
		$uncached = $this->createMock(HordeImapClient::class);
		$uncached->expects(self::once())
			->method('logout');
		$this->imapClientFactory->method('getClient')
			->willReturnCallback(fn (Account $account, bool $useCache) => $useCache ? $cached : $uncached);
		$this->pool->imap($account);
		$this->pool->imap($account, false);

		$this->pool->release($account);
	}

	public function testReleaseIgnoresImapLogoutFailure(): void {
		$account = $this->imapAccount(1);
		$client = $this->createMock(HordeImapClient::class);
		$client->expects(self::once())
			->method('logout')
			->willThrowException(new Horde_Imap_Client_Exception('Connection reset'));
		$newClient = $this->createMock(HordeImapClient::class);
		$this->imapClientFactory->expects(self::exactly(2))
			->method('getClient')
			->willReturnOnConsecutiveCalls($client, $newClient);
		$this->pool->imap($account);

		$this->pool->release($account);

		self::assertSame($newClient, $this->pool->imap($account));
	}

	public function testReleaseAllLogsOutEveryImapClient(): void {
		$accountA = $this->imapAccount(1);
		$accountB = $this->imapAccount(2);
		$clientA = $this->createMock(HordeImapClient::class);
		$clientA->expects(self::once())
			->method('logout');
		$clientB = $this->createMock(HordeImapClient::class);
		$clientB->expects(self::once())
			->method('logout');
		$this->imapClientFactory->method('getClient')
			->willReturnCallback(fn (Account $account) => match ($account) {
				$accountA => $clientA,
				$accountB => $clientB,
			});
		$this->pool->imap($accountA);
		$this->pool->imap($accountB);

		$this->pool->releaseAll();
		$this->pool->releaseAll();
	}
}
