<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Integration;

use Exception;
use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use function json_decode;

class MicrosoftIntegration {
	private ITimeFactory $timeFactory;
	private IConfig $config;
	private ICrypto $crypto;
	private IClientService $clientService;
	private IURLGenerator $urlGenerator;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $timeFactory,
		IConfig $config,
		ICrypto $crypto,
		IClientService $clientService,
		IURLGenerator $urlGenerator,
		LoggerInterface $logger) {
		$this->timeFactory = $timeFactory;
		$this->clientService = $clientService;
		$this->crypto = $crypto;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
	}

	public function configure(?string $tenantId, string $clientId, string $clientSecret): void {
		if ($tenantId !== null) {
			$this->config->setAppValue(
				Application::APP_ID,
				'microsoft_oauth_tenant_id',
				$tenantId
			);
		}
		$this->config->setAppValue(
			Application::APP_ID,
			'microsoft_oauth_client_id',
			$clientId
		);
		$this->config->setAppValue(
			Application::APP_ID,
			'microsoft_oauth_client_secret',
			$this->crypto->encrypt($clientSecret),
		);
	}

	public function unlink() {
		$this->config->deleteAppValue(
			Application::APP_ID,
			'microsoft_oauth_tenant_id',
		);
		$this->config->deleteAppValue(
			Application::APP_ID,
			'microsoft_oauth_client_id',
		);
		$this->config->deleteAppValue(
			Application::APP_ID,
			'microsoft_oauth_client_secret',
		);
	}

	public function getTenantId(): ?string {
		return $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_tenant_id', 'common');
	}

	public function getClientId(): ?string {
		$value = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_id');
		if ($value === '') {
			return null;
		}
		return $value;
	}

	public function isMicrosoftOauthAccount(Account $account): bool {
		return $account->getMailAccount()->getInboundHost() === 'outlook.office365.com'
			&& $account->getMailAccount()->getAuthMethod() === 'xoauth2';
	}

	public function finishConnect(Account $account,
		string $code): Account {
		$tenantId = $this->getTenantId();
		$clientId = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_id');
		$encryptedClientSecret = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_secret');
		if (empty($tenantId) || empty($clientId) || empty($encryptedClientSecret)) {
			// This is highly unexpected
			$this->logger->critical('Can not finish Microsoft account linking due to missing client secrets');
			return $account;
		}
		$clientSecret = $this->crypto->decrypt($encryptedClientSecret);
		$httpClient = $this->clientService->newClient();
		try {
			$response = $httpClient->post("https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token", [
				'form_params' => [
					'client_id' => $clientId,
					'client_secret' => $clientSecret,
					'grant_type' => 'authorization_code',
					'redirect_uri' => $this->getRedirectUrl(),
					'code' => $code,
				],
			]);
		} catch (Exception $e) {
			$this->logger->error('Could not link Microsoft account: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return $account;
		}

		$data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$encryptedRefreshToken = $this->crypto->encrypt($data['refresh_token']);
		$account->getMailAccount()->setOauthRefreshToken($encryptedRefreshToken);
		$encryptedAccessToken = $this->crypto->encrypt($data['access_token']);
		$account->getMailAccount()->setOauthAccessToken($encryptedAccessToken);
		$account->getMailAccount()->setOauthTokenTtl($this->timeFactory->getTime() + $data['expires_in']);
		return $account;
	}

	public function refresh(Account $account): Account {
		if ($account->getMailAccount()->getOauthTokenTtl() === null || $account->getMailAccount()->getOauthRefreshToken() === null) {
			// Account is not authorized yet
			return $account;
		}

		// Only refresh if the token expires in the next minute
		if ($this->timeFactory->getTime() <= ($account->getMailAccount()->getOauthTokenTtl() - 60)) {
			// No need to refresh yet
			return $account;
		}

		$tenantId = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_tenant_id');
		$clientId = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_id');
		$encryptedClientSecret = $this->config->getAppValue(Application::APP_ID, 'microsoft_oauth_client_secret');
		if (empty($tenantId) || empty($clientId) || empty($encryptedClientSecret)) {
			// Nothing to do here
			return $account;
		}

		$refreshToken = $this->crypto->decrypt($account->getMailAccount()->getOauthRefreshToken());
		$clientSecret = $this->crypto->decrypt($encryptedClientSecret);
		$httpClient = $this->clientService->newClient();
		try {
			$response = $httpClient->post("https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token", [
				'content-type' => 'application/json',
				'form_params' => [
					'client_id' => $clientId,
					'client_secret' => $clientSecret,
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken,
				],
			]);
		} catch (Exception $e) {
			$this->logger->warning('Could not refresh oauth token: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return $account;
		}

		$data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$encryptedAccessToken = $this->crypto->encrypt($data['access_token']);
		$account->getMailAccount()->setOauthAccessToken($encryptedAccessToken);
		$account->getMailAccount()->setOauthTokenTtl($this->timeFactory->getTime() + $data['expires_in']);

		return $account;
	}

	public function getRedirectUrl(): string {
		return $this->urlGenerator->linkToRouteAbsolute('mail.microsoftIntegration.oauthRedirect');
	}
}
