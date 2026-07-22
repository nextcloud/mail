<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Integration;

use Exception;
use OCA\Mail\Account;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use function json_decode;

/**
 * Authenticate provisioned accounts against the configured IMAP/SMTP servers with the
 * logged-in user's OIDC access token (XOAUTH2) instead of a stored password.
 *
 * Token life cycle:
 *  - Interactive requests: the current (auto-refreshed) login token is read from the
 *    user_oidc session via ExternalTokenRequestedEvent and mirrored into the account row
 *    (ProvisioningMiddleware and refresh()).
 *  - Background jobs: refresh() renews the mirrored token at the IdP's token endpoint
 *    using the stored refresh token, exactly like the Google/Microsoft integrations.
 *
 * The IdP token endpoint and client credentials are read from the user_oidc provider
 * configuration; this integration is inert when user_oidc is not installed.
 */
class OidcIntegration {
	/** @var array<int, string> */
	private array $tokenEndpoints = [];

	public function __construct(
		private ITimeFactory $timeFactory,
		private ICrypto $crypto,
		private IClientService $clientService,
		private IEventDispatcher $eventDispatcher,
		private IAppManager $appManager,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Whether accounts can be provisioned with OIDC token authentication, i.e. the
	 * user_oidc app is installed, enabled and exposes the token API this integration
	 * builds on.
	 */
	public function isAvailable(): bool {
		return $this->appManager->isEnabledForAnyone('user_oidc')
			&& class_exists(\OCA\UserOIDC\Event\ExternalTokenRequestedEvent::class);
	}

	public function isOidcAccount(Account $account): bool {
		// Only provisioned accounts use the OIDC token. Google/Microsoft xoauth2 accounts
		// are linked by the user and never carry a provisioning id.
		return $account->getMailAccount()->getProvisioningId() !== null
			&& $account->getMailAccount()->getAuthMethod() === 'xoauth2';
	}

	/**
	 * Mirror the current user_oidc session token into the account row if it is fresher
	 * than what is stored. No-op without an active user session holding a token.
	 */
	public function updateFromSession(Account $account): Account {
		if (!class_exists(\OCA\UserOIDC\Event\ExternalTokenRequestedEvent::class)) {
			return $account;
		}

		// The session token belongs to the logged-in user. Never mirror it into someone
		// else's account, e.g. when acting on a delegated account.
		$sessionUser = $this->userSession->getUser();
		if ($sessionUser === null || $sessionUser->getUID() !== $account->getMailAccount()->getUserId()) {
			return $account;
		}

		try {
			$event = new \OCA\UserOIDC\Event\ExternalTokenRequestedEvent();
			$this->eventDispatcher->dispatchTyped($event);
			$token = $event->getToken();
		} catch (\Throwable $e) {
			$this->logger->debug('Could not get OIDC token from user_oidc session: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return $account;
		}

		if ($token === null || $token->isExpired()) {
			return $account;
		}

		$expiresAt = $this->timeFactory->getTime() + $token->getExpiresInFromNow();
		$storedTtl = $account->getMailAccount()->getOauthTokenTtl();
		if ($storedTtl !== null && $expiresAt <= $storedTtl + 60 && $account->getMailAccount()->getOauthRefreshToken() !== null) {
			// Stored token is as fresh as the session token
			return $account;
		}

		$account->getMailAccount()->setOauthAccessToken($this->crypto->encrypt($token->getAccessToken()));
		$refreshToken = $token->getRefreshToken();
		if ($refreshToken !== null) {
			$account->getMailAccount()->setOauthRefreshToken($this->crypto->encrypt($refreshToken));
		}
		$account->getMailAccount()->setOauthTokenTtl($expiresAt);

		$this->logger->debug('Mirrored OIDC session token to mail account {accountId}', [
			'accountId' => $account->getId(),
		]);
		return $account;
	}

	/**
	 * Make sure the stored access token is valid, renewing it from the user session or
	 * with the stored refresh token (background jobs) if it is expired or expiring.
	 */
	public function refresh(Account $account): Account {
		// Only refresh if the token expires in the next minute
		$ttl = $account->getMailAccount()->getOauthTokenTtl();
		if ($ttl !== null && $this->timeFactory->getTime() <= ($ttl - 60)) {
			return $account;
		}

		// Prefer the live session token on interactive requests
		$account = $this->updateFromSession($account);
		$ttl = $account->getMailAccount()->getOauthTokenTtl();
		if ($ttl !== null && $this->timeFactory->getTime() <= ($ttl - 60)) {
			return $account;
		}

		$encryptedRefreshToken = $account->getMailAccount()->getOauthRefreshToken();
		if ($encryptedRefreshToken === null) {
			// Account has not been seeded with a token yet
			return $account;
		}

		$providerConfig = $this->getProviderConfig();
		if ($providerConfig === null) {
			return $account;
		}
		[$tokenEndpoint, $clientId, $clientSecret] = $providerConfig;

		try {
			$refreshToken = $this->crypto->decrypt($encryptedRefreshToken);
		} catch (Exception $e) {
			$this->logger->warning('Could not decrypt OIDC refresh token for account {accountId}: ' . $e->getMessage(), [
				'exception' => $e,
				'accountId' => $account->getId(),
			]);
			return $account;
		}

		$httpClient = $this->clientService->newClient();
		try {
			$response = $httpClient->post($tokenEndpoint, [
				'body' => [
					'client_id' => $clientId,
					'client_secret' => $clientSecret,
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshToken,
				],
			]);
		} catch (Exception $e) {
			$this->logger->warning('Could not refresh OIDC token for account {accountId}: ' . $e->getMessage(), [
				'exception' => $e,
				'accountId' => $account->getId(),
			]);
			return $account;
		}

		$data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$account->getMailAccount()->setOauthAccessToken($this->crypto->encrypt($data['access_token']));
		if (isset($data['refresh_token'])) {
			// The IdP may rotate the refresh token
			$account->getMailAccount()->setOauthRefreshToken($this->crypto->encrypt($data['refresh_token']));
		}
		$account->getMailAccount()->setOauthTokenTtl($this->timeFactory->getTime() + $data['expires_in']);

		return $account;
	}

	/**
	 * Read the token endpoint and client credentials from the user_oidc provider.
	 *
	 * @return array{0: string, 1: string, 2: string}|null [token endpoint, client id, client secret]
	 */
	private function getProviderConfig(): ?array {
		if (!class_exists(\OCA\UserOIDC\Db\ProviderMapper::class)) {
			$this->logger->debug('Cannot refresh OIDC mail token, user_oidc is not installed');
			return null;
		}

		try {
			$providerMapper = \OCP\Server::get(\OCA\UserOIDC\Db\ProviderMapper::class);
			$providers = $providerMapper->getProviders();
		} catch (\Throwable $e) {
			$this->logger->warning('Could not read user_oidc providers: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		}

		if ($providers === []) {
			$this->logger->debug('Cannot refresh OIDC mail token, no user_oidc provider is configured');
			return null;
		}
		if (count($providers) > 1) {
			$this->logger->debug('Multiple user_oidc providers configured, using the first one for mail token refresh');
		}
		$provider = $providers[0];

		$clientSecret = $provider->getClientSecret();
		if ($clientSecret !== '') {
			try {
				$clientSecret = $this->crypto->decrypt($clientSecret);
			} catch (Exception $e) {
				$this->logger->warning('Could not decrypt user_oidc client secret: ' . $e->getMessage(), [
					'exception' => $e,
				]);
				return null;
			}
		}

		$tokenEndpoint = $this->getTokenEndpoint($provider);
		if ($tokenEndpoint === null) {
			return null;
		}

		return [$tokenEndpoint, $provider->getClientId(), $clientSecret];
	}

	private function getTokenEndpoint(\OCA\UserOIDC\Db\Provider $provider): ?string {
		$cached = $this->tokenEndpoints[$provider->getId()] ?? null;
		if ($cached !== null) {
			return $cached;
		}

		$discoveryUrl = $provider->getDiscoveryEndpoint();
		if ($discoveryUrl === null || $discoveryUrl === '') {
			$this->logger->warning('user_oidc provider has no discovery endpoint');
			return null;
		}

		try {
			$response = $this->clientService->newClient()->get($discoveryUrl);
			$discovery = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not fetch OIDC discovery document: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		}

		$tokenEndpoint = $discovery['token_endpoint'] ?? null;
		if (!is_string($tokenEndpoint) || $tokenEndpoint === '') {
			$this->logger->warning('OIDC discovery document has no token_endpoint');
			return null;
		}

		$this->tokenEndpoints[$provider->getId()] = $tokenEndpoint;
		return $tokenEndpoint;
	}
}
