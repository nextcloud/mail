<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Provider;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Provider\MailProvider;
use OCA\Mail\Provider\MailService;
use OCA\Mail\Service\AccountService;
use OCP\Mail\Provider\Address as MailAddress;
use Psr\Container\ContainerInterface;

class MailProviderTest extends TestCase {

	/** @var ContainerInterfaceMockObject*/
	private $containerInterface;
	/** @var AccountServiceMockObject*/
	private $accountService;

	protected function setUp(): void {
		parent::setUp();

		$this->containerInterface = $this->createMock(ContainerInterface::class);
		$this->accountService = $this->createMock(AccountService::class);

	}

	public function testId(): void {

		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test set by constructor
		$this->assertEquals('mail-application', $mailProvider->id());

	}

	public function testLabel(): void {
		
		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test set by constructor
		$this->assertEquals('Mail Application', $mailProvider->label());

	}

	public function testHasServices(): void {
		
		// construct dummy mail account
		$mailAccount = new Account(new MailAccount([
			'accountId' => 100,
			'accountName' => 'User One',
			'emailAddress' => 'user1@testing.com',
			'imapHost' => '',
			'imapPort' => '',
			'imapSslMode' => false,
			'imapUser' => '',
			'smtpHost' => '',
			'smtpPort' => '',
			'smtpSslMode' => false,
			'smtpUser' => '',
		]));
		// define account services find
		$this->accountService
		->expects($this->any())
		->method('findByUserId')
		->will(
			$this->returnValueMap(
				[
					['user0', []],
					['user1', [100 => $mailAccount]]
				]
			)
		);
		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test result with no services found
		$this->assertFalse($mailProvider->hasServices('user0'));
		// test result with services found
		$this->assertTrue($mailProvider->hasServices('user1'));

	}

	public function testListServices(): void {
		
		// construct dummy mail account
		$mailAccount = new Account(new MailAccount([
			'accountId' => 100,
			'accountName' => 'User One',
			'emailAddress' => 'user1@testing.com',
			'imapHost' => '',
			'imapPort' => '',
			'imapSslMode' => false,
			'imapUser' => '',
			'smtpHost' => '',
			'smtpPort' => '',
			'smtpSslMode' => false,
			'smtpUser' => '',
		]));
		// construct dummy mail service
		$mailService = new MailService(
			$this->containerInterface,
			'user1',
			'100',
			'User One',
			new MailAddress('user1@testing.com', 'User One')
		);
		// define account services find
		$this->accountService
		->expects($this->any())
		->method('findByUserId')
		->will(
			$this->returnValueMap(
				[
					['user0', []],
					['user1', [$mailAccount]]
				]
			)
		);
		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test result with no services found
		$this->assertEquals([], $mailProvider->listServices('user0'));
		// test result with services found
		$this->assertEquals([100 => $mailService], $mailProvider->listServices('user1'));

	}

	public function testFindServiceById(): void {
		
		// construct dummy mail account
		$mailAccount = new Account(new MailAccount([
			'accountId' => 100,
			'accountName' => 'User One',
			'emailAddress' => 'user1@testing.com',
			'imapHost' => '',
			'imapPort' => '',
			'imapSslMode' => false,
			'imapUser' => '',
			'smtpHost' => '',
			'smtpPort' => '',
			'smtpSslMode' => false,
			'smtpUser' => '',
		]));
		// construct dummy mail service
		$mailService = new MailService(
			$this->containerInterface,
			'user1',
			'100',
			'User One',
			new MailAddress('user1@testing.com', 'User One')
		);
		// define account services find
		$this->accountService
		->expects($this->any())
		->method('find')
		->will(
			$this->returnValueMap(
				[
					['user0', 100, $this->throwException(new ClientException())],
					['user1', 100, $mailAccount]
				]
			)
		);
		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test result with no services found
		$this->assertEquals(null, $mailProvider->findServiceById('user0', '100'));
		// test result with services found
		$this->assertEquals($mailService, $mailProvider->findServiceById('user1', '100'));

	}

	public function testFindServiceByAddress(): void {
		
		// construct dummy mail account
		$mailAccount = new Account(new MailAccount([
			'accountId' => 100,
			'accountName' => 'User One',
			'emailAddress' => 'user1@testing.com',
			'imapHost' => '',
			'imapPort' => '',
			'imapSslMode' => false,
			'imapUser' => '',
			'smtpHost' => '',
			'smtpPort' => '',
			'smtpSslMode' => false,
			'smtpUser' => '',
		]));
		// construct dummy mail service
		$mailService = new MailService(
			$this->containerInterface,
			'user1',
			'100',
			'User One',
			new MailAddress('user1@testing.com', 'User One')
		);
		// define account services find
		$this->accountService
		->expects($this->any())
		->method('findByUserIdAndAddress')
		->will(
			$this->returnValueMap(
				[
					['user0', 'user0@testing.com', $this->throwException(new ClientException())],
					['user1', 'user1@testing.com', [$mailAccount]]
				]
			)
		);
		// construct mail provider
		$mailProvider = new MailProvider($this->containerInterface, $this->accountService);
		// test result with no services found
		$this->assertEquals(null, $mailProvider->findServiceByAddress('user0', 'user0@testing.com'));
		// test result with services found
		$this->assertEquals($mailService, $mailProvider->findServiceByAddress('user1', 'user1@testing.com'));

	}

}
