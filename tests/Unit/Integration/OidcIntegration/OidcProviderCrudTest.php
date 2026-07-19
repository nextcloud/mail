<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration\OidcIntegration;

use OCA\Mail\Db\OidcProvider;

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
}
