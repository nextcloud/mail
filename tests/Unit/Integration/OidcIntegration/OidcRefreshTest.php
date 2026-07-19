<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration\OidcIntegration;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;

/**
 * Renewing the access token with the stored refresh token.
 */
class OidcRefreshTest extends OidcIntegrationTestCase {
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

	public function testRefreshNoopWhenProviderGone(): void {
		$account = $this->account('alice@example.com');
		$account->getMailAccount()->setOauthRefreshToken('enc-refresh');
		$account->getMailAccount()->setOauthTokenTtl(1000);
		$this->timeFactory->method('getTime')->willReturn(1000);
		$this->providerMapper->method('findByEmailDomain')->willReturn(null);

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
}
