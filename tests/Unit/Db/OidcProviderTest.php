<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\OidcProvider;

class OidcProviderTest extends TestCase {
	private function fullProvider(): OidcProvider {
		$provider = new OidcProvider();
		$provider->setId(3);
		$provider->setName('Company IdP');
		$provider->setEmailDomain('example.com');
		$provider->setImapHost('imap.example.com');
		$provider->setImapPort(993);
		$provider->setImapSslMode('ssl');
		$provider->setSmtpHost('smtp.example.com');
		$provider->setSmtpPort(587);
		$provider->setSmtpSslMode('tls');
		$provider->setClientId('mail');
		$provider->setManualEndpoints(false);
		$provider->setDiscoveryUrl('https://idp.example.com/.well-known/openid-configuration');
		$provider->setAuthorizationEndpoint('');
		$provider->setTokenEndpoint('');
		$provider->setScope('openid email');
		return $provider;
	}

	public function testJsonSerializeMasksSetSecret(): void {
		$provider = $this->fullProvider();
		$provider->setClientSecret('super-secret');

		$json = $provider->jsonSerialize();

		$this->assertSame(OidcProvider::CLIENT_SECRET_PLACEHOLDER, $json['clientSecret']);
	}

	public function testJsonSerializeNullSecretWhenUnset(): void {
		$provider = $this->fullProvider();

		$json = $provider->jsonSerialize();

		$this->assertNull($json['clientSecret']);
	}

	public function testJsonSerializeExposesAllFields(): void {
		$provider = $this->fullProvider();
		$provider->setManualEndpoints(true);
		$provider->setAuthorizationEndpoint('https://idp.example.com/authorize');
		$provider->setTokenEndpoint('https://idp.example.com/token');

		$json = $provider->jsonSerialize();

		$this->assertSame(3, $json['id']);
		$this->assertSame('Company IdP', $json['name']);
		$this->assertSame('example.com', $json['emailDomain']);
		$this->assertSame('imap.example.com', $json['imapHost']);
		$this->assertSame(993, $json['imapPort']);
		$this->assertSame('ssl', $json['imapSslMode']);
		$this->assertSame('smtp.example.com', $json['smtpHost']);
		$this->assertSame(587, $json['smtpPort']);
		$this->assertSame('tls', $json['smtpSslMode']);
		$this->assertSame('mail', $json['clientId']);
		$this->assertTrue($json['manualEndpoints']);
		$this->assertSame('https://idp.example.com/authorize', $json['authorizationEndpoint']);
		$this->assertSame('https://idp.example.com/token', $json['tokenEndpoint']);
		$this->assertSame('openid email', $json['scope']);
	}
}
