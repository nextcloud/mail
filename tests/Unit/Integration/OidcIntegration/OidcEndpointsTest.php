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
 * Resolving authorization/token/introspection endpoints from discovery or manual config.
 */
class OidcEndpointsTest extends OidcIntegrationTestCase {
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

	public function testIntrospectionUnavailableWithManualEndpointsAndNoIntrospectionUrl(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://idp.example.com/token');
		$provider->setIntrospectionEndpoint('');
		$this->clientService->expects($this->never())->method('newClient');

		$this->assertNull($this->integration->introspectToken($provider, 'tok', 'refresh_token'));
	}

	public function testIntrospectionUsesManualIntrospectionEndpoint(): void {
		$provider = $this->provider();
		$provider->setManualEndpoints(true);
		$provider->setTokenEndpoint('https://idp.example.com/token');
		$provider->setIntrospectionEndpoint('https://manual.example.com/introspect');
		$this->crypto->method('decrypt')->willReturn('plain-secret');

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode(['active' => false]));
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with('https://manual.example.com/introspect', $this->callback(function (array $options): bool {
				return $options['body']['token'] === 'tok'
					&& $options['body']['token_type_hint'] === 'refresh_token';
			}))
			->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$this->assertFalse($this->integration->introspectToken($provider, 'tok', 'refresh_token'));
	}
}
