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
 * Exchanging an authorization code for tokens.
 */
class OidcConnectTest extends OidcIntegrationTestCase {
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

	public function testFinishConnectIgnoresUnexpectedResponse(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://idp.example.com/token');
		$account = $this->account('alice@example.com');
		$this->crypto->method('decrypt')->willReturn('plain-secret');

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode(['error' => 'invalid_grant']));
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->finishConnect($provider, $account, 'code');

		$this->assertNull($result->getMailAccount()->getOauthAccessToken());
	}
}
