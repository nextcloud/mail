<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\OidcProvider;
use OCA\Mail\Db\OidcProviderMapper;
use OCA\Mail\Integration\OidcIntegration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class OidcIntegrationTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private ICrypto&MockObject $crypto;
	private IClientService&MockObject $clientService;
	private IURLGenerator&MockObject $urlGenerator;
	private OidcProviderMapper&MockObject $providerMapper;
	private LoggerInterface&MockObject $logger;
	private ICache&MockObject $cache;
	private OidcIntegration $integration;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->providerMapper = $this->createMock(OidcProviderMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cache = $this->createMock(ICache::class);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($this->cache);

		$this->integration = new OidcIntegration(
			$this->timeFactory,
			$this->crypto,
			$this->clientService,
			$this->urlGenerator,
			$this->providerMapper,
			$this->logger,
			$cacheFactory,
		);
	}

	private function account(string $email, string $authMethod = 'xoauth2'): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setEmail($email);
		$mailAccount->setAuthMethod($authMethod);
		return new Account($mailAccount);
	}

	private function provider(): OidcProvider {
		$provider = new OidcProvider();
		$provider->setId(1);
		$provider->setName('Test');
		$provider->setEmailDomain('example.com');
		$provider->setClientId('mail-client');
		$provider->setClientSecret('encrypted-secret');
		$provider->setDiscoveryUrl('https://idp.example.com/.well-known/openid-configuration');
		$provider->setScope('openid email offline_access');
		return $provider;
	}

	public function testGetProviderForAccountMatchesOnDomain(): void {
		$provider = $this->provider();
		$this->providerMapper->expects($this->once())
			->method('findByEmailDomain')
			->with('example.com')
			->willReturn($provider);

		$result = $this->integration->getProviderForAccount($this->account('alice@Example.com'));

		$this->assertSame($provider, $result);
	}

	public function testGetProviderForAccountReturnsNullForInvalidEmail(): void {
		$this->providerMapper->expects($this->never())->method('findByEmailDomain');

		$result = $this->integration->getProviderForAccount($this->account('not-an-email'));

		$this->assertNull($result);
	}

	public function testIsOidcAccountFalseForNonXoauth2(): void {
		$this->providerMapper->expects($this->never())->method('findByEmailDomain');

		$this->assertFalse($this->integration->isOidcAccount($this->account('alice@example.com', 'password')));
	}

	public function testIsOidcAccountTrueWhenProviderMatches(): void {
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());

		$this->assertTrue($this->integration->isOidcAccount($this->account('alice@example.com')));
	}

	public function testIsOidcAccountFalseWhenNoProviderMatches(): void {
		$this->providerMapper->method('findByEmailDomain')->willReturn(null);

		$this->assertFalse($this->integration->isOidcAccount($this->account('alice@example.com')));
	}

	public function testGetDiscoveryUsesCache(): void {
		$provider = $this->provider();
		$this->cache->method('get')
			->with($provider->getDiscoveryUrl())
			->willReturn(json_encode([
				'authorization_endpoint' => 'https://idp.example.com/auth',
				'token_endpoint' => 'https://idp.example.com/token',
			]));
		$this->clientService->expects($this->never())->method('newClient');

		$discovery = $this->integration->getDiscovery($provider);

		$this->assertSame('https://idp.example.com/token', $discovery['token_endpoint']);
	}

	public function testGetDiscoveryFetchesAndCaches(): void {
		$provider = $this->provider();
		$this->cache->method('get')->willReturn(null);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('get')
			->with($provider->getDiscoveryUrl())
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$this->cache->expects($this->once())->method('set');

		$discovery = $this->integration->getDiscovery($provider);

		$this->assertSame('https://idp.example.com/auth', $discovery['authorization_endpoint']);
	}

	public function testGetDiscoveryThrowsOnMissingEndpoints(): void {
		$provider = $this->provider();
		$this->cache->method('get')->willReturn(null);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode(['issuer' => 'https://idp.example.com']));
		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$this->expectException(\Exception::class);

		$this->integration->getDiscovery($provider);
	}

	public function testFinishConnectStoresTokens(): void {
		$provider = $this->provider();
		$account = $this->account('alice@example.com');

		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$this->crypto->method('decrypt')->with('encrypted-secret')->willReturn('plain-secret');
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'the-access-token',
			'refresh_token' => 'the-refresh-token',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with('https://idp.example.com/token', $this->callback(function (array $options): bool {
				$body = $options['body'];
				return $body['grant_type'] === 'authorization_code'
					&& $body['code'] === 'auth-code'
					&& $body['client_id'] === 'mail-client'
					&& $body['client_secret'] === 'plain-secret';
			}))
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->finishConnect($provider, $account, 'auth-code');

		$this->assertSame('enc:the-access-token', $result->getMailAccount()->getOauthAccessToken());
		$this->assertSame('enc:the-refresh-token', $result->getMailAccount()->getOauthRefreshToken());
		$this->assertSame(4600, $result->getMailAccount()->getOauthTokenTtl());
	}

	public function testFinishConnectSendsPkceVerifier(): void {
		$provider = $this->provider();
		$provider->setClientSecret(null);
		$account = $this->account('alice@example.com');

		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'the-access-token',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with('https://idp.example.com/token', $this->callback(function (array $options): bool {
				$body = $options['body'];
				return $body['code_verifier'] === 'verifier-123'
					&& !isset($body['client_secret']);
			}))
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$this->integration->finishConnect($provider, $account, 'auth-code', 'verifier-123');
	}

	public function testRefreshNoopWhenNotAuthorized(): void {
		$account = $this->account('alice@example.com');

		$this->clientService->expects($this->never())->method('newClient');

		$this->integration->refresh($account);
	}

	public function testRefreshNoopWhenTokenStillFresh(): void {
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(10000);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$this->clientService->expects($this->never())->method('newClient');

		$this->integration->refresh($account);
	}

	public function testRefreshUpdatesAccessToken(): void {
		$provider = $this->provider();
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);

		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn($provider);
		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$this->crypto->method('decrypt')->willReturnCallback(static function (string $v): string {
			return match ($v) {
				'enc-refresh' => 'plain-refresh',
				'encrypted-secret' => 'plain-secret',
				default => $v,
			};
		});
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'fresh-access-token',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with('https://idp.example.com/token', $this->callback(function (array $options): bool {
				$body = $options['body'];
				return $body['grant_type'] === 'refresh_token'
					&& $body['refresh_token'] === 'plain-refresh';
			}))
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->refresh($account);

		$this->assertSame('enc:fresh-access-token', $result->getMailAccount()->getOauthAccessToken());
		$this->assertSame(4600, $result->getMailAccount()->getOauthTokenTtl());
	}

	public function testGetEndpointsFromDiscovery(): void {
		$provider = $this->provider();
		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));

		$endpoints = $this->integration->getEndpoints($provider);

		$this->assertSame('https://idp.example.com/auth', $endpoints['authorization_endpoint']);
		$this->assertSame('https://idp.example.com/token', $endpoints['token_endpoint']);
	}

	public function testGetEndpointsManualSkipsDiscovery(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setAuthorizationEndpoint('https://manual.example.com/authorize');
		$provider->setTokenEndpoint('https://manual.example.com/token');
		$this->cache->expects($this->never())->method('get');
		$this->clientService->expects($this->never())->method('newClient');

		$endpoints = $this->integration->getEndpoints($provider);

		$this->assertSame('https://manual.example.com/authorize', $endpoints['authorization_endpoint']);
		$this->assertSame('https://manual.example.com/token', $endpoints['token_endpoint']);
	}

	public function testFinishConnectUsesManualTokenEndpoint(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://manual.example.com/token');
		$account = $this->account('alice@example.com');

		$this->crypto->method('decrypt')->willReturn('plain-secret');
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);
		$this->timeFactory->method('getTime')->willReturn(1000);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'tok',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with('https://manual.example.com/token', $this->anything())
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$this->integration->finishConnect($provider, $account, 'auth-code');
	}

	public function testGetProvidersDelegatesToMapper(): void {
		$this->providerMapper->expects($this->once())
			->method('getAll')
			->willReturn([$this->provider()]);

		$this->assertCount(1, $this->integration->getProviders());
	}

	public function testCreateProviderEncryptsSecretAndInserts(): void {
		$provider = $this->provider();
		$provider->setClientSecret('plain-secret');
		$this->providerMapper->method('validate')->willReturn($provider);
		$this->crypto->expects($this->once())
			->method('encrypt')
			->with('plain-secret')
			->willReturn('enc:plain-secret');
		$this->providerMapper->expects($this->once())
			->method('insert')
			->willReturnArgument(0);

		$result = $this->integration->createProvider(['id' => 99, 'name' => 'x']);

		$this->assertSame('enc:plain-secret', $result->getClientSecret());
	}

	public function testCreateProviderWithoutSecretDoesNotEncrypt(): void {
		$provider = new OidcProvider();
		$this->providerMapper->method('validate')->willReturn($provider);
		$this->crypto->expects($this->never())->method('encrypt');
		$this->providerMapper->expects($this->once())->method('insert')->willReturnArgument(0);

		$this->integration->createProvider([]);
	}

	public function testUpdateProviderRequiresId(): void {
		$this->providerMapper->method('validate')->willReturn(new OidcProvider());

		$this->expectException(\InvalidArgumentException::class);

		$this->integration->updateProvider(['name' => 'x']);
	}

	public function testUpdateProviderEncryptsAndUpdates(): void {
		$provider = $this->provider();
		$provider->setClientSecret('plain-secret');
		$this->providerMapper->method('validate')->willReturn($provider);
		$this->crypto->method('encrypt')->willReturn('enc:plain-secret');
		$this->providerMapper->expects($this->once())->method('update')->willReturnArgument(0);

		$result = $this->integration->updateProvider(['id' => 1]);

		$this->assertSame('enc:plain-secret', $result->getClientSecret());
	}

	public function testDeleteProviderDeletesWhenFound(): void {
		$provider = $this->provider();
		$this->providerMapper->method('get')->with(1)->willReturn($provider);
		$this->providerMapper->expects($this->once())->method('delete')->with($provider);

		$this->integration->deleteProvider(1);
	}

	public function testDeleteProviderNoopWhenMissing(): void {
		$this->providerMapper->method('get')->willReturn(null);
		$this->providerMapper->expects($this->never())->method('delete');

		$this->integration->deleteProvider(99);
	}

	public function testFinishConnectReturnsAccountOnDiscoveryFailure(): void {
		$provider = $this->provider();
		$account = $this->account('alice@example.com');
		$this->cache->method('get')->willReturn(null);
		$client = $this->createMock(IClient::class);
		$client->method('get')->willThrowException(new \Exception('discovery down'));
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->finishConnect($provider, $account, 'code');

		$this->assertNull($result->getMailAccount()->getOauthAccessToken());
	}

	public function testFinishConnectReturnsAccountOnHttpFailure(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://idp.example.com/token');
		$account = $this->account('alice@example.com');
		$this->crypto->method('decrypt')->willReturn('plain-secret');
		$client = $this->createMock(IClient::class);
		$client->method('post')->willThrowException(new \Exception('boom'));
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->finishConnect($provider, $account, 'code');

		$this->assertNull($result->getMailAccount()->getOauthAccessToken());
	}

	public function testRefreshReturnsAccountOnEndpointFailure(): void {
		$provider = $this->provider();
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn($provider);
		$this->cache->method('get')->willReturn(null);
		$client = $this->createMock(IClient::class);
		$client->method('get')->willThrowException(new \Exception('discovery down'));
		$this->clientService->method('newClient')->willReturn($client);

		$this->integration->refresh($account);

		$this->assertSame('enc-refresh', $account->getMailAccount()->getOauthRefreshToken());
	}

	public function testRefreshReturnsAccountOnHttpFailure(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://idp.example.com/token');
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn($provider);
		$this->crypto->method('decrypt')->willReturn('plain-refresh');
		$client = $this->createMock(IClient::class);
		$client->method('post')->willThrowException(new \Exception('boom'));
		$this->clientService->method('newClient')->willReturn($client);

		$this->integration->refresh($account);

		$this->assertSame('enc-refresh', $account->getMailAccount()->getOauthRefreshToken());
	}

	public function testRefreshRotatesRefreshToken(): void {
		$provider = $this->provider();
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn($provider);
		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$this->crypto->method('decrypt')->willReturn('plain');
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'new-access',
			'refresh_token' => 'rotated-refresh',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->refresh($account);

		$this->assertSame('enc:rotated-refresh', $result->getMailAccount()->getOauthRefreshToken());
	}

	public function testGetProviderDelegatesToMapper(): void {
		$provider = $this->provider();
		$this->providerMapper->expects($this->once())
			->method('get')
			->with(5)
			->willReturn($provider);

		$this->assertSame($provider, $this->integration->getProvider(5));
	}

	public function testGetAuthorizationUrlBuildsUrl(): void {
		$provider = $this->provider();
		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
		]));
		$this->urlGenerator->method('linkToRouteAbsolute')
			->with('mail.oidcIntegration.oauthRedirect')
			->willReturn('https://cloud.example.com/oidc-auth');

		$url = $this->integration->getAuthorizationUrl($provider, 'the-state');

		$this->assertStringStartsWith('https://idp.example.com/auth?', $url);
		parse_str(parse_url($url, PHP_URL_QUERY), $query);
		$this->assertSame('mail-client', $query['client_id']);
		$this->assertSame('code', $query['response_type']);
		$this->assertSame('the-state', $query['state']);
		$this->assertSame('https://cloud.example.com/oidc-auth', $query['redirect_uri']);
		$this->assertSame('openid email offline_access', $query['scope']);
	}

	public function testGetAuthorizationUrlAppendsToExistingQuery(): void {
		$provider = $this->provider();
		$this->cache->method('get')->willReturn(json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth?foo=bar',
			'token_endpoint' => 'https://idp.example.com/token',
		]));

		$url = $this->integration->getAuthorizationUrl($provider, 'the-state');

		$this->assertStringStartsWith('https://idp.example.com/auth?foo=bar&', $url);
	}

	public function testRefreshNoopWhenProviderGone(): void {
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn(null);

		$this->clientService->expects($this->never())->method('newClient');

		$this->integration->refresh($account);
	}
}
