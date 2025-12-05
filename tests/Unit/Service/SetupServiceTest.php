<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use Horde_Mail_Exception;
use Horde_Mail_Transport_Smtphorde;
use InvalidArgumentException;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SetupService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SetupServiceTest extends TestCase {
	private const ACCOUNT_NAME = 'Test Account';
	private const EMAIL_ADDRESS = 'test@example.com';
	private const IMAP_HOST = 'imap.example.com';
	private const IMAP_PORT = 993;
	private const IMAP_SSL_MODE = 'ssl';
	private const IMAP_USER = 'test@example.com';
	private const IMAP_PASSWORD = 'imap-password';
	private const SMTP_HOST = 'smtp.example.com';
	private const SMTP_PORT = 465;
	private const SMTP_SSL_MODE = 'ssl';
	private const SMTP_USER = 'test@example.com';
	private const SMTP_PASSWORD = 'smtp-password';
	private const USER_ID = 'user123';
	private const AUTH_METHOD_PASSWORD = 'password';
	private const AUTH_METHOD_OAUTH2 = 'xoauth2';

	private AccountService&MockObject $accountService;
	private ICrypto&MockObject $crypto;
	private SmtpClientFactory&MockObject $smtpClientFactory;
	private IMAPClientFactory&MockObject $imapClientFactory;
	private LoggerInterface&MockObject $logger;
	private TagMapper&MockObject $tagMapper;
	private SetupService $setupService;

	protected function setUp(): void {
		parent::setUp();

		$this->accountService = $this->createMock(AccountService::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->smtpClientFactory = $this->createMock(SmtpClientFactory::class);
		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->tagMapper = $this->createMock(TagMapper::class);

		$this->setupService = new SetupService(
			$this->accountService,
			$this->crypto,
			$this->smtpClientFactory,
			$this->imapClientFactory,
			$this->logger,
			$this->tagMapper
		);
	}

	private function mockSuccessfulImapConnection(): Horde_Imap_Client_Socket&MockObject {
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$imapClient->expects(self::once())->method('login');
		$imapClient->expects(self::once())->method('logout');

		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($imapClient);

		return $imapClient;
	}

	private function mockSuccessfulSmtpConnection(): Horde_Mail_Transport_Smtphorde&MockObject {
		$smtpTransport = $this->createMock(Horde_Mail_Transport_Smtphorde::class);
		$smtpTransport->expects(self::once())->method('getSMTPObject');

		$this->smtpClientFactory->expects(self::once())
			->method('create')
			->willReturn($smtpTransport);

		return $smtpTransport;
	}

	private function mockPasswordEncryption(): void {
		$this->crypto->expects(self::exactly(2))
			->method('encrypt')
			->willReturnOnConsecutiveCalls('encrypted-imap-password', 'encrypted-smtp-password');
	}

	private function assertAccountPropertiesMatch(
		MailAccount $account,
		string $accountName,
		string $emailAddress,
		string $imapHost,
		int $imapPort,
		string $imapSslMode,
		string $imapUser,
		string $smtpHost,
		int $smtpPort,
		string $smtpSslMode,
		string $smtpUser,
		string $uid,
		string $authMethod,
	): void {
		self::assertSame($accountName, $account->getName(), 'Account name does not match');
		self::assertSame($emailAddress, $account->getEmail(), 'Email address does not match');
		self::assertSame($imapHost, $account->getInboundHost(), 'IMAP host does not match');
		self::assertSame($imapPort, $account->getInboundPort(), 'IMAP port does not match');
		self::assertSame($imapSslMode, $account->getInboundSslMode(), 'IMAP SSL mode does not match');
		self::assertSame($imapUser, $account->getInboundUser(), 'IMAP user does not match');
		self::assertSame($smtpHost, $account->getOutboundHost(), 'SMTP host does not match');
		self::assertSame($smtpPort, $account->getOutboundPort(), 'SMTP port does not match');
		self::assertSame($smtpSslMode, $account->getOutboundSslMode(), 'SMTP SSL mode does not match');
		self::assertSame($smtpUser, $account->getOutboundUser(), 'SMTP user does not match');
		self::assertSame($uid, $account->getUserId(), 'User ID does not match');
		self::assertSame($authMethod, $account->getAuthMethod(), 'Auth method does not match');
	}

	public function testCreateNewAccountWithPasswordAuth(): void {
		$this->mockPasswordEncryption();

		$this->logger->expects(self::once())
			->method('info')
			->with('Setting up manually configured account');

		$debugCalls = [];
		$this->logger->expects(self::exactly(2))
			->method('debug')
			->willReturnCallback(function (string $message, array $context = []) use (&$debugCalls): void {
				$debugCalls[] = ['message' => $message, 'context' => $context];
			});

		$this->mockSuccessfulImapConnection();
		$this->mockSuccessfulSmtpConnection();

		$this->accountService->expects(self::once())
			->method('save')
			->with(self::callback(function (MailAccount $account): bool {
				$this->assertAccountPropertiesMatch(
					$account,
					self::ACCOUNT_NAME,
					self::EMAIL_ADDRESS,
					self::IMAP_HOST,
					self::IMAP_PORT,
					self::IMAP_SSL_MODE,
					self::IMAP_USER,
					self::SMTP_HOST,
					self::SMTP_PORT,
					self::SMTP_SSL_MODE,
					self::SMTP_USER,
					self::USER_ID,
					self::AUTH_METHOD_PASSWORD
				);
				return true;
			}));

		$this->tagMapper->expects(self::once())
			->method('createDefaultTags')
			->with(self::isInstanceOf(MailAccount::class));

		$result = $this->setupService->createNewAccount(
			self::ACCOUNT_NAME,
			self::EMAIL_ADDRESS,
			self::IMAP_HOST,
			self::IMAP_PORT,
			self::IMAP_SSL_MODE,
			self::IMAP_USER,
			self::IMAP_PASSWORD,
			self::SMTP_HOST,
			self::SMTP_PORT,
			self::SMTP_SSL_MODE,
			self::SMTP_USER,
			self::SMTP_PASSWORD,
			self::USER_ID,
			self::AUTH_METHOD_PASSWORD
		);

		self::assertInstanceOf(Account::class, $result);

		// Verify debug log calls
		self::assertCount(2, $debugCalls);
		self::assertSame('Connecting to account {account}', $debugCalls[0]['message']);
		self::assertSame(['account' => self::EMAIL_ADDRESS], $debugCalls[0]['context']);
		self::assertStringContainsString('account created ', $debugCalls[1]['message']);
		self::assertSame([], $debugCalls[1]['context']);
	}

	public function testCreateNewAccountWithOAuth2(): void {
		$this->crypto->expects(self::never())->method('encrypt');

		$this->logger->expects(self::once())
			->method('info')
			->with('Setting up manually configured account');
		$this->logger->expects(self::once())
			->method('debug')
			->with(self::stringContains('account created '));

		$this->imapClientFactory->expects(self::never())->method('getClient');
		$this->smtpClientFactory->expects(self::never())->method('create');

		$this->accountService->expects(self::once())
			->method('save')
			->with(self::callback(function (MailAccount $account): bool {
				return $account->getAuthMethod() === self::AUTH_METHOD_OAUTH2;
			}));

		$this->tagMapper->expects(self::once())->method('createDefaultTags');

		$result = $this->setupService->createNewAccount(
			'OAuth2 Account',
			'oauth@example.com',
			self::IMAP_HOST,
			self::IMAP_PORT,
			self::IMAP_SSL_MODE,
			'oauth@example.com',
			null,
			self::SMTP_HOST,
			self::SMTP_PORT,
			self::SMTP_SSL_MODE,
			'oauth@example.com',
			null,
			'user456',
			self::AUTH_METHOD_OAUTH2
		);

		self::assertInstanceOf(Account::class, $result);
	}

	public function testCreateNewAccountWithInvalidAuthMethod(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid auth method invalid');

		$this->setupService->createNewAccount(
			self::ACCOUNT_NAME,
			self::EMAIL_ADDRESS,
			self::IMAP_HOST,
			self::IMAP_PORT,
			self::IMAP_SSL_MODE,
			self::IMAP_USER,
			self::IMAP_PASSWORD,
			self::SMTP_HOST,
			self::SMTP_PORT,
			self::SMTP_SSL_MODE,
			self::SMTP_USER,
			self::SMTP_PASSWORD,
			self::USER_ID,
			'invalid'
		);
	}

	public function testCreateNewAccountImapConnectionFailure(): void {
		$this->expectException(CouldNotConnectException::class);

		$this->mockPasswordEncryption();

		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$imapClient->expects(self::once())
			->method('login')
			->willThrowException(new Horde_Imap_Client_Exception('Connection failed'));
		$imapClient->expects(self::once())
			->method('logout');

		$this->imapClientFactory->expects(self::once())
			->method('getClient')
			->willReturn($imapClient);

		$this->setupService->createNewAccount(
			self::ACCOUNT_NAME,
			self::EMAIL_ADDRESS,
			self::IMAP_HOST,
			self::IMAP_PORT,
			self::IMAP_SSL_MODE,
			self::IMAP_USER,
			self::IMAP_PASSWORD,
			self::SMTP_HOST,
			self::SMTP_PORT,
			self::SMTP_SSL_MODE,
			self::SMTP_USER,
			self::SMTP_PASSWORD,
			self::USER_ID,
			self::AUTH_METHOD_PASSWORD
		);
	}

	public function testCreateNewAccountSmtpConnectionFailure(): void {
		$this->expectException(CouldNotConnectException::class);

		$this->mockPasswordEncryption();
		$this->mockSuccessfulImapConnection();

		$smtpTransport = $this->createMock(Horde_Mail_Transport_Smtphorde::class);
		$smtpTransport->expects(self::once())
			->method('getSMTPObject')
			->willThrowException(new Horde_Mail_Exception('SMTP connection failed'));

		$this->smtpClientFactory->expects(self::once())
			->method('create')
			->willReturn($smtpTransport);

		$this->setupService->createNewAccount(
			self::ACCOUNT_NAME,
			self::EMAIL_ADDRESS,
			self::IMAP_HOST,
			self::IMAP_PORT,
			self::IMAP_SSL_MODE,
			self::IMAP_USER,
			self::IMAP_PASSWORD,
			self::SMTP_HOST,
			self::SMTP_PORT,
			self::SMTP_SSL_MODE,
			self::SMTP_USER,
			self::SMTP_PASSWORD,
			self::USER_ID,
			self::AUTH_METHOD_PASSWORD
		);
	}
}
