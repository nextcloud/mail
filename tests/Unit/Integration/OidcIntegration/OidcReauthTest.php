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
 * Detecting a grant that can no longer be renewed, so the user is asked to reconnect.
 */
class OidcReauthTest extends OidcIntegrationTestCase {
	public function testRefreshFlagsReauthWhenNoRefreshTokenIssued(): void {
		$account = $this->expiredAccount(null);
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->clientService->expects($this->never())->method('newClient');

		$result = $this->integration->refresh($account);

		$this->assertTrue($result->getMailAccount()->getOauthNeedsReauth());
	}

	public function testRefreshFlagsReauthWhenIntrospectionSaysInactive(): void {
		$account = $this->expiredAccount('enc-refresh');
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->cache->method('get')->willReturn($this->discoveryWithIntrospection());
		$this->crypto->method('decrypt')->willReturn('plain');
		$this->clientService->method('newClient')->willReturn($this->failingRefreshClient(['active' => false]));

		$result = $this->integration->refresh($account);

		$this->assertTrue($result->getMailAccount()->getOauthNeedsReauth());
	}

	public function testRefreshDoesNotFlagWhenTokenStillActive(): void {
		$account = $this->expiredAccount('enc-refresh');
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->cache->method('get')->willReturn($this->discoveryWithIntrospection());
		$this->crypto->method('decrypt')->willReturn('plain');
		$this->clientService->method('newClient')->willReturn($this->failingRefreshClient(['active' => true]));

		$result = $this->integration->refresh($account);

		$this->assertNotTrue($result->getMailAccount()->getOauthNeedsReauth());
	}

	public function testRefreshDoesNotFlagWhenIntrospectionUnavailable(): void {
		$account = $this->expiredAccount('enc-refresh');
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->cache->method('get')->willReturn($this->discoveryWithIntrospection());
		$this->crypto->method('decrypt')->willReturn('plain');
		$this->clientService->method('newClient')->willReturn($this->failingRefreshClient(null));

		$result = $this->integration->refresh($account);

		$this->assertNotTrue($result->getMailAccount()->getOauthNeedsReauth());
	}

	public function testRefreshClearsReauthFlagOnSuccess(): void {
		$account = $this->expiredAccount('enc-refresh');
		$account->getMailAccount()->setOauthNeedsReauth(true);
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->cache->method('get')->willReturn($this->discoveryWithIntrospection());
		$this->crypto->method('decrypt')->willReturn('plain');
		$this->crypto->method('encrypt')->willReturnCallback(static fn (string $v): string => 'enc:' . $v);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'fresh',
			'expires_in' => 3600,
		]));
		$client = $this->createMock(IClient::class);
		$client->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($client);

		$result = $this->integration->refresh($account);

		$this->assertFalse($result->getMailAccount()->getOauthNeedsReauth());
	}
}
