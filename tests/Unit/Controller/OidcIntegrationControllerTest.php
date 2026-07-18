<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\OidcIntegrationController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\OidcProvider;
use OCA\Mail\Exception\InvalidOauthStateException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Integration\OidcIntegration;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OauthStateService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class OidcIntegrationControllerTest extends TestCase {
	private OidcIntegration&MockObject $oidcIntegration;
	private AccountService&MockObject $accountService;
	private LoggerInterface&MockObject $logger;
	private MailboxSync&MockObject $mailboxSync;
	private OauthStateService&MockObject $oauthStateService;
	private OidcIntegrationController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->oidcIntegration = $this->createMock(OidcIntegration::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);
		$this->oauthStateService = $this->createMock(OauthStateService::class);

		$this->controller = new OidcIntegrationController(
			$this->createMock(IRequest::class),
			'alice',
			$this->oidcIntegration,
			$this->accountService,
			$this->logger,
			$this->mailboxSync,
			$this->oauthStateService,
		);
	}

	public function testIndexReturnsProviders(): void {
		$provider = new OidcProvider();
		$this->oidcIntegration->method('getProviders')->willReturn([$provider]);

		$response = $this->controller->index();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame([$provider], $response->getData());
	}

	public function testCreateSuccess(): void {
		$provider = new OidcProvider();
		$this->oidcIntegration->expects($this->once())
			->method('createProvider')
			->with(['name' => 'Keycloak'])
			->willReturn($provider);

		$response = $this->controller->create(['name' => 'Keycloak']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame($provider, $response->getData());
	}

	public function testCreateValidationFailure(): void {
		$exception = new ValidationException();
		$exception->setField('name', false);
		$this->oidcIntegration->method('createProvider')->willThrowException($exception);

		$response = $this->controller->create([]);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testCreateGenericFailure(): void {
		$this->oidcIntegration->method('createProvider')
			->willThrowException(new \RuntimeException('db down'));

		$response = $this->controller->create(['name' => 'x']);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testUpdateGenericFailure(): void {
		$this->oidcIntegration->method('updateProvider')
			->willThrowException(new \RuntimeException('db down'));

		$response = $this->controller->update(1, ['name' => 'x']);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testUpdateValidationFailure(): void {
		$exception = new ValidationException();
		$exception->setField('emailDomain', false);
		$this->oidcIntegration->method('updateProvider')->willThrowException($exception);

		$response = $this->controller->update(1, []);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testUpdateMergesId(): void {
		$provider = new OidcProvider();
		$this->oidcIntegration->expects($this->once())
			->method('updateProvider')
			->with(['name' => 'Keycloak', 'id' => 5])
			->willReturn($provider);

		$response = $this->controller->update(5, ['name' => 'Keycloak']);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testDestroyDeletesProvider(): void {
		$this->oidcIntegration->expects($this->once())->method('deleteProvider')->with(3);

		$response = $this->controller->destroy(3);

		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testAuthorizeRedirectsToProvider(): void {
		$provider = new OidcProvider();
		$this->oidcIntegration->method('getProvider')->with(7)->willReturn($provider);
		$this->oidcIntegration->method('getAuthorizationUrl')
			->with($provider, 'the-state')
			->willReturn('https://idp.example.com/auth?state=the-state');

		$response = $this->controller->authorize(7, 'the-state');

		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertSame('https://idp.example.com/auth?state=the-state', $response->getRedirectURL());
	}

	public function testAuthorizeUnknownProviderReturnsDone(): void {
		$this->oidcIntegration->method('getProvider')->willReturn(null);
		$this->oidcIntegration->expects($this->never())->method('getAuthorizationUrl');

		$response = $this->controller->authorize(99, 'the-state');

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testAuthorizeDiscoveryFailureReturnsDone(): void {
		$provider = new OidcProvider();
		$this->oidcIntegration->method('getProvider')->willReturn($provider);
		$this->oidcIntegration->method('getAuthorizationUrl')
			->willThrowException(new \Exception('discovery down'));

		$response = $this->controller->authorize(7, 'the-state');

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testOauthRedirectWithoutCodeReturnsDone(): void {
		$this->oauthStateService->expects($this->never())->method('validateAndConsume');

		$response = $this->controller->oauthRedirect(null, null, null);

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testOauthRedirectInvalidStateReturnsDone(): void {
		$this->oauthStateService->method('validateAndConsume')
			->willThrowException(new InvalidOauthStateException());

		$response = $this->controller->oauthRedirect('the-code', 'bad-state', null);

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testOauthRedirectNoProviderReturnsDone(): void {
		$account = new Account(new MailAccount());
		$this->oauthStateService->method('validateAndConsume')->willReturn(42);
		$this->accountService->method('find')->with('alice', 42)->willReturn($account);
		$this->oidcIntegration->method('getProviderForAccount')->willReturn(null);
		$this->oidcIntegration->expects($this->never())->method('finishConnect');

		$response = $this->controller->oauthRedirect('the-code', 'good-state', null);

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testOauthRedirectSurvivesSyncFailure(): void {
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$provider = new OidcProvider();
		$this->oauthStateService->method('validateAndConsume')->willReturn(42);
		$this->accountService->method('find')->willReturn($account);
		$this->oidcIntegration->method('getProviderForAccount')->willReturn($provider);
		$this->oidcIntegration->method('finishConnect')->willReturn($account);
		$this->mailboxSync->method('sync')
			->willThrowException(new ServiceException('sync failed'));

		$response = $this->controller->oauthRedirect('the-code', 'good-state', null);

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}

	public function testOauthRedirectHappyPathStoresAndSyncs(): void {
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$provider = new OidcProvider();
		$this->oauthStateService->method('validateAndConsume')->willReturn(42);
		$this->accountService->method('find')->with('alice', 42)->willReturn($account);
		$this->oidcIntegration->method('getProviderForAccount')->willReturn($provider);
		$this->oidcIntegration->expects($this->once())
			->method('finishConnect')
			->with($provider, $account, 'the-code')
			->willReturn($account);
		$this->accountService->expects($this->once())->method('update')->with($mailAccount);
		$this->mailboxSync->expects($this->once())->method('sync')->with($account, $this->logger);

		$response = $this->controller->oauthRedirect('the-code', 'good-state', null);

		$this->assertInstanceOf(StandaloneTemplateResponse::class, $response);
	}
}
