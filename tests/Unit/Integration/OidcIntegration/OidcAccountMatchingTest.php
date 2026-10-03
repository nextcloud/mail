<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration\OidcIntegration;

/**
 * Matching a mail account to a configured provider by its email domain.
 */
class OidcAccountMatchingTest extends OidcIntegrationTestCase {
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
}
