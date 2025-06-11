<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Unit\SetupChecks;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OC\L10N\L10N;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\ProvisioningMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\SetupChecks\MailConnectionPerformance;
use OCA\Mail\SetupChecks\MicroTime;
use OCP\IL10N;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;

class MailConnectionPerformanceTest extends TestCase {

	private IL10N&MockObject $l10n;
	private TestLogger $logger;
	private ProvisioningMapper&MockObject $provisioningMapper;
	private MailAccountMapper&MockObject $accountMapper;
	private IMAPClientFactory&MockObject $clientFactory;
	private MicroTime&MockObject $microtime;
	private MailConnectionPerformance $check;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(L10N::class);
		$this->logger = new TestLogger();
		$this->provisioningMapper = $this->createMock(ProvisioningMapper::class);
		$this->accountMapper = $this->createMock(MailAccountMapper::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->microtime = $this->createMock(MicroTime::class);

		$this->check = new MailConnectionPerformance(
			$this->l10n,
			$this->logger,
			$this->provisioningMapper,
			$this->accountMapper,
			$this->clientFactory,
			$this->microtime
		);
	}

	public function testNoHosts(): void {
		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn([]);

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public function testNoAccounts(): void {
		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn(['imap.example.com']);

		$this->accountMapper->expects($this->once())
			->method('getRandomAccountIdsByImapHost')
			->with('imap.example.com')
			->willReturn([]);

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public function testConnectionSuccess(): void {
		$account = new MailAccount(
			[
				'accountId' => 42,
				'imapHost' => 'imap.example.com',
				'imapUser' => 'user',
				'imapPassword' => 'password',
				'imapPort' => 143,
				'imapSslMode' => 'none',
			]
		);

		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn(['imap.example.com']);

		$this->accountMapper->expects($this->once())
			->method('getRandomAccountIdsByImapHost')
			->with('imap.example.com')
			->willReturn([42]);
		$this->accountMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($account);

		$client->expects($this->once())
			->method('listMailboxes')
			->with('*')
			->willReturn(['INBOX' => []]);
		$client->expects($this->once())
			->method('status')
			->with('INBOX')
			->willReturn([]);

		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account))
			->willReturn($client);

		$this->microtime->method('getNumeric')
			->willReturnOnConsecutiveCalls(0, .2, .2);

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());

	}

	public function testConnectionWarning(): void {
		$account = new MailAccount(
			[
				'accountId' => 42,
				'imapHost' => 'imap.example.com',
				'imapUser' => 'user',
				'imapPassword' => 'password',
				'imapPort' => 143,
				'imapSslMode' => 'none',
			]
		);

		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn(['imap.example.com']);

		$this->accountMapper->expects($this->once())
			->method('getRandomAccountIdsByImapHost')
			->with('imap.example.com')
			->willReturn([42]);
		$this->accountMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($account);

		$client->expects($this->once())
			->method('listMailboxes')
			->with('*')
			->willReturn(['INBOX' => []]);
		$client->expects($this->once())
			->method('status')
			->with('INBOX')
			->willReturn([]);

		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account))
			->willReturn($client);

		$this->microtime->method('getNumeric')
			->willReturnOnConsecutiveCalls(0, 2, 3);

		$result = $this->check->run();

		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());

	}

	public function testConnectionFailure(): void {
		$account = new MailAccount(
			[
				'accountId' => 42,
				'imapHost' => 'imap.example.com',
				'imapUser' => 'user',
				'imapPassword' => 'password',
				'imapPort' => 143,
				'imapSslMode' => 'none',
			]
		);

		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn(['imap.example.com']);

		$this->accountMapper->expects($this->once())
			->method('getRandomAccountIdsByImapHost')
			->with('imap.example.com')
			->willReturn([42]);
		$this->accountMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($account);

		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account))
			->willReturn($client);

		$client->expects($this->once())
			->method('login')
			->willThrowException(new \Exception('Login failed'));

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
		$this->assertTrue($this->logger->hasWarningThatContains('Error occurred while performing system check on mail account: 42'));
	}

	public function testClientFailure(): void {
		$account = new MailAccount(
			[
				'accountId' => 42,
				'imapHost' => 'imap.example.com',
				'imapUser' => 'user',
				'imapPassword' => 'password',
				'imapPort' => 143,
				'imapSslMode' => 'none',
			]
		);
		$this->provisioningMapper->expects($this->once())
			->method('findUniqueImapHosts')
			->willReturn(['imap.example.com']);
		$this->accountMapper->expects($this->once())
			->method('getRandomAccountIdsByImapHost')
			->with('imap.example.com')
			->willReturn([42]);
		$this->accountMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($account);
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->with(new Account($account))
			->willThrowException(new ServiceException('Something about decryption'));

		$result = $this->check->run();

		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
		$this->assertTrue($this->logger->hasWarningThatContains('Error occurred while getting IMAP client for setup check: Something about decryption'));
	}

}
