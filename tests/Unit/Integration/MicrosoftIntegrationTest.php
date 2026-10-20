<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Integration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Account;
use OCA\Mail\ConfigLexicon;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MicrosoftIntegrationTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private IAppConfig&MockObject $appConfig;
	private ICrypto&MockObject $crypto;
	private IClientService&MockObject $clientService;
	private IClient&MockObject $client;
	private LoggerInterface&MockObject $logger;
	private MicrosoftIntegration $integration;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->client = $this->createMock(IClient::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->timeFactory->method('getTime')->willReturn(10000);
		$this->appConfig->method('getValueString')
			->willReturnCallback(fn (string $app, string $key): string => match ($key) {
				ConfigLexicon::MICROSOFT_OAUTH_TENANT_ID => 'tenant',
				ConfigLexicon::MICROSOFT_OAUTH_CLIENT_ID => 'client-id',
				ConfigLexicon::MICROSOFT_OAUTH_CLIENT_SECRET => 'encrypted-secret',
				default => '',
			});
		$this->crypto->method('encrypt')
			->willReturnCallback(fn (string $plaintext): string => "encrypted:$plaintext");
		$this->crypto->method('decrypt')
			->willReturnCallback(fn (string $ciphertext): string => match ($ciphertext) {
				'encrypted-secret' => 'secret',
				'encrypted:old-refresh-token' => 'old-refresh-token',
			});
		$this->clientService->method('newClient')->willReturn($this->client);

		$this->integration = new MicrosoftIntegration(
			$this->timeFactory,
			$this->appConfig,
			$this->crypto,
			$this->clientService,
			$this->createMock(IURLGenerator::class),
			$this->logger,
		);
	}

	private function createExpiredAccount(): Account {
		$mailAccount = new MailAccount();
		$mailAccount->setOauthAccessToken('encrypted:old-access-token');
		$mailAccount->setOauthRefreshToken('encrypted:old-refresh-token');
		$mailAccount->setOauthTokenTtl(5000);
		return new Account($mailAccount);
	}

	private function mockTokenResponse(array $data): void {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(json_encode($data));
		$this->client->expects($this->once())
			->method('post')
			->with(
				'https://login.microsoftonline.com/tenant/oauth2/v2.0/token',
				$this->callback(fn (array $options): bool => $options['form_params']['grant_type'] === 'refresh_token'
					&& $options['form_params']['refresh_token'] === 'old-refresh-token'),
			)
			->willReturn($response);
	}

	public function testRefreshStoresRotatedRefreshToken(): void {
		$account = $this->createExpiredAccount();
		$this->mockTokenResponse([
			'access_token' => 'new-access-token',
			'refresh_token' => 'new-refresh-token',
			'expires_in' => 3600,
		]);

		$updated = $this->integration->refresh($account);

		$this->assertSame('encrypted:new-access-token', $updated->getMailAccount()->getOauthAccessToken());
		$this->assertSame('encrypted:new-refresh-token', $updated->getMailAccount()->getOauthRefreshToken());
		$this->assertSame(13600, $updated->getMailAccount()->getOauthTokenTtl());
	}

	public function testRefreshKeepsRefreshTokenWhenNoneIsReturned(): void {
		$account = $this->createExpiredAccount();
		$this->mockTokenResponse([
			'access_token' => 'new-access-token',
			'expires_in' => 3600,
		]);

		$updated = $this->integration->refresh($account);

		$this->assertSame('encrypted:new-access-token', $updated->getMailAccount()->getOauthAccessToken());
		$this->assertSame('encrypted:old-refresh-token', $updated->getMailAccount()->getOauthRefreshToken());
	}

	public function testRefreshKeepsTokensWhenTheRequestFails(): void {
		$account = $this->createExpiredAccount();
		$this->client->method('post')->willThrowException(new Exception('invalid_grant'));
		$this->logger->expects($this->once())->method('warning');

		$updated = $this->integration->refresh($account);

		$this->assertSame('encrypted:old-access-token', $updated->getMailAccount()->getOauthAccessToken());
		$this->assertSame('encrypted:old-refresh-token', $updated->getMailAccount()->getOauthRefreshToken());
		$this->assertSame(5000, $updated->getMailAccount()->getOauthTokenTtl());
	}

	public function testRefreshSkipsTokenThatIsStillValid(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setOauthRefreshToken('encrypted:old-refresh-token');
		$mailAccount->setOauthTokenTtl(20000);
		$this->client->expects($this->never())->method('post');

		$this->integration->refresh(new Account($mailAccount));
	}
}
