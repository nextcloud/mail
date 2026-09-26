<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration\OidcIntegration;

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

/**
 * Shared fixture for the {@see OidcIntegration} unit tests: the mocked collaborators
 * and the account/provider builders the individual test classes share.
 */
abstract class OidcIntegrationTestCase extends TestCase {
	protected ITimeFactory&MockObject $timeFactory;
	protected ICrypto&MockObject $crypto;
	protected IClientService&MockObject $clientService;
	protected IURLGenerator&MockObject $urlGenerator;
	protected OidcProviderMapper&MockObject $providerMapper;
	protected LoggerInterface&MockObject $logger;
	protected ICache&MockObject $cache;
	protected OidcIntegration $integration;

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

	protected function account(string $email, string $authMethod = 'xoauth2'): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setEmail($email);
		$mailAccount->setAuthMethod($authMethod);
		return new Account($mailAccount);
	}

	protected function provider(): OidcProvider {
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

	/**
	 * Account whose access token has just expired, with the given refresh token.
	 */
	protected function expiredAccount(?string $refreshToken): Account {
		$account = $this->account('alice@example.com');
		if ($refreshToken !== null) {
			$account->getMailAccount()->setOauthRefreshToken($refreshToken);
		}
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		return $account;
	}

	protected function discoveryWithIntrospection(): string {
		return json_encode([
			'authorization_endpoint' => 'https://idp.example.com/auth',
			'token_endpoint' => 'https://idp.example.com/token',
			'introspection_endpoint' => 'https://idp.example.com/introspect',
		]);
	}

	/**
	 * Token endpoint fails; introspection answers with the given payload.
	 */
	protected function failingRefreshClient(?array $introspection): IClient {
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturnCallback(
			function (string $url) use ($introspection): IResponse {
				if (str_contains($url, '/introspect')) {
					if ($introspection === null) {
						throw new \Exception('introspection down');
					}
					$response = $this->createMock(IResponse::class);
					$response->method('getBody')->willReturn(json_encode($introspection));
					return $response;
				}
				throw new \Exception('refresh rejected');
			},
		);
		return $client;
	}
}
