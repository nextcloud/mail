<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\OidcProvider;
use OCA\Mail\Db\OidcProviderMapper;
use OCA\Mail\Exception\ValidationException;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class OidcProviderMapperTest extends TestCase {
	private IDBConnection&MockObject $db;
	private OidcProviderMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(IDBConnection::class);
		$this->mapper = new OidcProviderMapper($this->db);
	}

	private function validData(): array {
		return [
			'name' => 'Company Keycloak',
			'emailDomain' => 'example.com',
			'imapHost' => 'imap.example.com',
			'imapPort' => 993,
			'imapSslMode' => 'ssl',
			'smtpHost' => 'smtp.example.com',
			'smtpPort' => 587,
			'smtpSslMode' => 'tls',
			'clientId' => 'mail-client',
			'clientSecret' => 's3cret',
			'discoveryUrl' => 'https://idp.example.com/.well-known/openid-configuration',
		];
	}

	public function testValidateBuildsProvider(): void {
		$provider = $this->mapper->validate($this->validData());

		$this->assertInstanceOf(OidcProvider::class, $provider);
		$this->assertSame('Company Keycloak', $provider->getName());
		$this->assertSame('example.com', $provider->getEmailDomain());
		$this->assertSame('imap.example.com', $provider->getImapHost());
		$this->assertSame(993, $provider->getImapPort());
		$this->assertSame('smtp.example.com', $provider->getSmtpHost());
		$this->assertSame(587, $provider->getSmtpPort());
		$this->assertSame('mail-client', $provider->getClientId());
		$this->assertSame('s3cret', $provider->getClientSecret());
	}

	public function testValidateLowercasesEmailDomain(): void {
		$data = $this->validData();
		$data['emailDomain'] = 'Example.COM';

		$provider = $this->mapper->validate($data);

		$this->assertSame('example.com', $provider->getEmailDomain());
	}

	public function testValidateDefaultsScope(): void {
		$provider = $this->mapper->validate($this->validData());

		$this->assertSame('openid email offline_access', $provider->getScope());
	}

	public function testValidateKeepsExplicitScope(): void {
		$data = $this->validData();
		$data['scope'] = 'openid email';

		$provider = $this->mapper->validate($data);

		$this->assertSame('openid email', $provider->getScope());
	}

	public function testValidateSetsIdWhenGiven(): void {
		$data = $this->validData();
		$data['id'] = 7;

		$provider = $this->mapper->validate($data);

		$this->assertSame(7, $provider->getId());
	}

	public function testValidateCastsPortsToInt(): void {
		$data = $this->validData();
		$data['imapPort'] = '993';
		$data['smtpPort'] = '587';

		$provider = $this->mapper->validate($data);

		$this->assertSame(993, $provider->getImapPort());
		$this->assertSame(587, $provider->getSmtpPort());
	}

	public function testValidateIgnoresPlaceholderSecret(): void {
		$data = $this->validData();
		$data['clientSecret'] = OidcProvider::CLIENT_SECRET_PLACEHOLDER;

		$provider = $this->mapper->validate($data);

		$this->assertNull($provider->getClientSecret());
	}

	public function testValidateDefaultsToDiscoveryMode(): void {
		$provider = $this->mapper->validate($this->validData());

		$this->assertFalse($provider->getManualEndpoints());
		$this->assertSame('https://idp.example.com/.well-known/openid-configuration', $provider->getDiscoveryUrl());
		$this->assertSame('', $provider->getAuthorizationEndpoint());
		$this->assertSame('', $provider->getTokenEndpoint());
	}

	public function testValidateManualEndpoints(): void {
		$data = $this->validData();
		unset($data['discoveryUrl']);
		$data['manualEndpoints'] = true;
		$data['authorizationEndpoint'] = 'https://idp.example.com/authorize';
		$data['tokenEndpoint'] = 'https://idp.example.com/token';

		$provider = $this->mapper->validate($data);

		$this->assertTrue($provider->getManualEndpoints());
		$this->assertSame('https://idp.example.com/authorize', $provider->getAuthorizationEndpoint());
		$this->assertSame('https://idp.example.com/token', $provider->getTokenEndpoint());
		$this->assertSame('', $provider->getDiscoveryUrl());
	}

	public function testValidateManualEndpointsRequiresBothEndpoints(): void {
		$data = $this->validData();
		unset($data['discoveryUrl']);
		$data['manualEndpoints'] = true;
		$data['authorizationEndpoint'] = 'https://idp.example.com/authorize';

		try {
			$this->mapper->validate($data);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertArrayHasKey('tokenEndpoint', $e->getFields());
			$this->assertArrayNotHasKey('discoveryUrl', $e->getFields());
		}
	}

	public function testValidateDiscoveryModeRequiresDiscoveryUrl(): void {
		$data = $this->validData();
		unset($data['discoveryUrl']);

		try {
			$this->mapper->validate($data);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertArrayHasKey('discoveryUrl', $e->getFields());
		}
	}

	public function testValidateRejectsMissingRequiredFields(): void {
		$data = $this->validData();
		unset($data['name'], $data['clientId']);

		try {
			$this->mapper->validate($data);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertArrayHasKey('name', $e->getFields());
			$this->assertArrayHasKey('clientId', $e->getFields());
		}
	}

	public function testValidateRejectsEmptyString(): void {
		$data = $this->validData();
		$data['emailDomain'] = '';

		try {
			$this->mapper->validate($data);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertArrayHasKey('emailDomain', $e->getFields());
		}
	}

	public function testValidateRejectsZeroPorts(): void {
		$data = $this->validData();
		$data['imapPort'] = 0;
		$data['smtpPort'] = 0;

		try {
			$this->mapper->validate($data);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertArrayHasKey('imapPort', $e->getFields());
			$this->assertArrayHasKey('smtpPort', $e->getFields());
		}
	}
}
