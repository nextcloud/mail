<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Integration\GoogleIntegration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class GoogleIntegrationTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private GoogleIntegration $integration;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->integration = new GoogleIntegration(
			$this->createMock(ITimeFactory::class),
			$this->appConfig,
			$this->createMock(ICrypto::class),
			$this->createMock(IClientService::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testGetClientId(): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with(Application::APP_ID, ConfigLexicon::GOOGLE_OAUTH_CLIENT_ID)
			->willReturn('client-id');

		$this->assertSame('client-id', $this->integration->getClientId());
	}

	public function testGetClientIdNotConfigured(): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with(Application::APP_ID, ConfigLexicon::GOOGLE_OAUTH_CLIENT_ID)
			->willReturn('');

		$this->assertSame('', $this->integration->getClientId());
	}
}
