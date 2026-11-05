<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration\OidcIntegration;

use OCA\Mail\Db\OidcProvider;
use OCA\Mail\Exception\OidcProviderNotFoundException;
use OCA\Mail\Exception\ValidationException;

/**
 * Admin CRUD for the configured providers.
 */
class OidcProviderCrudTest extends OidcIntegrationTestCase {
	public function testGetProvidersDelegatesToMapper(): void {
		$this->providerMapper->expects($this->once())
			->method('getAll')
			->willReturn([$this->provider()]);

		$this->assertCount(1, $this->integration->getProviders());
	}

	public function testGetProviderDelegatesToMapper(): void {
		$provider = $this->provider();
		$this->providerMapper->expects($this->once())
			->method('get')
			->with(5)
			->willReturn($provider);

		$this->assertSame($provider, $this->integration->getProvider(5));
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
		$provider = $this->provider();
		$provider->setClientSecret(null);
		$this->providerMapper->method('validate')->willReturn($provider);
		$this->crypto->expects($this->never())->method('encrypt');
		$this->providerMapper->expects($this->once())->method('insert')->willReturnArgument(0);

		$this->integration->createProvider([]);
	}

	public function testCreateProviderRejectsTakenEmailDomain(): void {
		$this->providerMapper->method('validate')->willReturn($this->provider());
		$taken = $this->provider();
		$taken->setId(3);
		$this->providerMapper->method('findByEmailDomain')->with('example.com')->willReturn($taken);
		$this->providerMapper->expects($this->never())->method('insert');

		try {
			$this->integration->createProvider([]);
			$this->fail('Expected ValidationException');
		} catch (ValidationException $e) {
			$this->assertSame(['emailDomain' => false], $e->getFields());
		}
	}

	public function testUpdateProviderThrowsWhenMissing(): void {
		$this->providerMapper->method('get')->with(1)->willReturn(null);
		$this->providerMapper->expects($this->never())->method('update');

		$this->expectException(OidcProviderNotFoundException::class);

		$this->integration->updateProvider(1, ['name' => 'x']);
	}

	public function testUpdateProviderEncryptsAndUpdates(): void {
		$this->providerMapper->method('get')->with(1)->willReturn($this->provider());
		$provider = $this->provider();
		$provider->setClientSecret('plain-secret');
		$this->providerMapper->expects($this->once())
			->method('validate')
			->with(['name' => 'x', 'id' => 1])
			->willReturn($provider);
		$this->crypto->method('encrypt')->willReturn('enc:plain-secret');
		$this->providerMapper->expects($this->once())->method('update')->willReturnArgument(0);

		$result = $this->integration->updateProvider(1, ['name' => 'x']);

		$this->assertSame('enc:plain-secret', $result->getClientSecret());
	}

	public function testUpdateProviderKeepsStoredSecretForPlaceholder(): void {
		$existing = $this->provider();
		$existing->setClientSecret('enc:stored');
		$this->providerMapper->method('get')->with(1)->willReturn($existing);
		$submitted = $this->provider();
		$submitted->setClientSecret(null);
		$this->providerMapper->method('validate')->willReturn($submitted);
		$this->crypto->expects($this->never())->method('encrypt');
		$this->providerMapper->expects($this->once())->method('update')->willReturnArgument(0);

		$result = $this->integration->updateProvider(1, ['clientSecret' => OidcProvider::CLIENT_SECRET_PLACEHOLDER]);

		$this->assertSame('enc:stored', $result->getClientSecret());
		$this->assertSame(OidcProvider::CLIENT_SECRET_PLACEHOLDER, $result->jsonSerialize()['clientSecret']);
	}

	public function testUpdateProviderAllowsKeepingOwnEmailDomain(): void {
		$this->providerMapper->method('get')->willReturn($this->provider());
		$this->providerMapper->method('validate')->willReturn($this->provider());
		$this->providerMapper->method('findByEmailDomain')->willReturn($this->provider());
		$this->providerMapper->expects($this->once())->method('update')->willReturnArgument(0);

		$this->integration->updateProvider(1, []);
	}

	public function testUpdateProviderRejectsEmailDomainOfAnotherProvider(): void {
		$this->providerMapper->method('get')->willReturn($this->provider());
		$this->providerMapper->method('validate')->willReturn($this->provider());
		$other = $this->provider();
		$other->setId(2);
		$this->providerMapper->method('findByEmailDomain')->willReturn($other);
		$this->providerMapper->expects($this->never())->method('update');

		$this->expectException(ValidationException::class);

		$this->integration->updateProvider(1, []);
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
}
