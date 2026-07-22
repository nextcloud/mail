<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Integration\OidcIntegration;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class OidcIntegrationTest extends TestCase {
	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var IClientService|MockObject */
	private $clientService;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var IAppManager|MockObject */
	private $appManager;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var OidcIntegration */
	private $integration;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->integration = new OidcIntegration(
			$this->timeFactory,
			$this->crypto,
			$this->clientService,
			$this->eventDispatcher,
			$this->appManager,
			$this->userSession,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testIsNotAvailableWithoutUserOidc(): void {
		$this->appManager->expects($this->once())
			->method('isEnabledForAnyone')
			->with('user_oidc')
			->willReturn(false);

		$this->assertFalse($this->integration->isAvailable());
	}

	public function testIsOidcAccountForProvisionedXoauth2Account(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProvisioningId(123);
		$mailAccount->setAuthMethod('xoauth2');
		$account = new Account($mailAccount);

		$this->assertTrue($this->integration->isOidcAccount($account));
	}

	public function testIsOidcAccountRejectsUnprovisionedXoauth2Account(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setAuthMethod('xoauth2');
		$account = new Account($mailAccount);

		$this->assertFalse($this->integration->isOidcAccount($account));
	}

	public function testIsOidcAccountRejectsPasswordAccount(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProvisioningId(123);
		$mailAccount->setAuthMethod('password');
		$account = new Account($mailAccount);

		$this->assertFalse($this->integration->isOidcAccount($account));
	}

	public function testRefreshSkipsWhenTokenIsFresh(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProvisioningId(123);
		$mailAccount->setAuthMethod('xoauth2');
		$mailAccount->setOauthAccessToken('enc-token');
		$mailAccount->setOauthTokenTtl(10000);
		$account = new Account($mailAccount);
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn(9000);
		$this->clientService->expects($this->never())
			->method('newClient');

		$actual = $this->integration->refresh($account);

		$this->assertSame('enc-token', $actual->getMailAccount()->getOauthAccessToken());
	}

	public function testUpdateFromSessionIgnoresForeignAccounts(): void {
		if (!class_exists(\OCA\UserOIDC\Event\ExternalTokenRequestedEvent::class)) {
			$this->markTestSkipped('user_oidc is not available');
		}
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('bob');
		$mailAccount->setProvisioningId(123);
		$mailAccount->setAuthMethod('xoauth2');
		$account = new Account($mailAccount);
		$sessionUser = $this->createMock(IUser::class);
		$sessionUser->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($sessionUser);
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$actual = $this->integration->updateFromSession($account);

		$this->assertNull($actual->getMailAccount()->getOauthAccessToken());
	}

	public function testRefreshWithoutRefreshTokenIsANoop(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setProvisioningId(123);
		$mailAccount->setAuthMethod('xoauth2');
		$mailAccount->setOauthTokenTtl(100);
		$account = new Account($mailAccount);
		$this->timeFactory->method('getTime')
			->willReturn(9000);
		$this->clientService->expects($this->never())
			->method('newClient');

		$actual = $this->integration->refresh($account);

		$this->assertNull($actual->getMailAccount()->getOauthAccessToken());
	}
}
