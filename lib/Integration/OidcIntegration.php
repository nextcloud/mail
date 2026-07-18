<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Integration;

use Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\OidcProvider;
use OCA\Mail\Db\OidcProviderMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use function json_decode;
use function json_encode;

/**
 * Generic OIDC/XOAUTH2 integration for individual mail accounts.
 *
 * Unlike the Google/Microsoft integrations this is not tied to a single hard-coded
 * provider: the admin configures 0..n {@see OidcProvider}s, each matched to an account
 * by the user's email domain. Endpoints are resolved from the provider's discovery
 * document and cached.
 */
class OidcIntegration {
	private const DISCOVERY_CACHE_PREFIX = 'mail_oidc_discovery';
	private const DISCOVERY_CACHE_TTL = 3600;

	private ICache $discoveryCache;

	public function __construct(
		private ITimeFactory $timeFactory,
		private ICrypto $crypto,
		private IClientService $clientService,
		private IURLGenerator $urlGenerator,
		private OidcProviderMapper $providerMapper,
		private LoggerInterface $logger,
		ICacheFactory $cacheFactory,
	) {
		$this->discoveryCache = $cacheFactory->createDistributed(self::DISCOVERY_CACHE_PREFIX);
	}

	/**
	 * All configured providers, ordered by email domain. The client secret is
	 * never exposed — {@see OidcProvider::jsonSerialize()} masks it.
	 *
	 * @return OidcProvider[]
	 */
	public function getProviders(): array {
		return $this->providerMapper->getAll();
	}

	public function getProvider(int $id): ?OidcProvider {
		return $this->providerMapper->get($id);
	}

	/**
	 * Build the IdP authorization-endpoint URL to start the interactive consent
	 * flow for a provider. The state is passed through and later validated by the
	 * redirect callback.
	 *
	 * @throws Exception when discovery can not be resolved
	 */
	public function getAuthorizationUrl(OidcProvider $provider, string $state): string {
		$authorizationEndpoint = $this->getEndpoints($provider)['authorization_endpoint'];
		$query = http_build_query([
			'client_id' => $provider->getClientId(),
			'redirect_uri' => $this->getRedirectUrl(),
			'response_type' => 'code',
			'response_mode' => 'query',
			'prompt' => 'consent',
			'scope' => $provider->getScope(),
			'state' => $state,
		]);
		$separator = str_contains($authorizationEndpoint, '?') ? '&' : '?';
		return $authorizationEndpoint . $separator . $query;
	}

	/**
	 * Persist a new provider from admin form data, encrypting the client secret.
	 *
	 * @throws \OCA\Mail\Exception\ValidationException
	 */
	public function createProvider(array $data): OidcProvider {
		unset($data['id']);
		$provider = $this->providerMapper->validate($data);
		$this->encryptClientSecret($provider);
		return $this->providerMapper->insert($provider);
	}

	/**
	 * Update an existing provider. When the submitted secret is the masking
	 * placeholder, {@see OidcProviderMapper::validate()} leaves it untouched so the
	 * stored secret is preserved.
	 *
	 * @throws \OCA\Mail\Exception\ValidationException
	 */
	public function updateProvider(array $data): OidcProvider {
		$provider = $this->providerMapper->validate($data);
		if ($provider->getId() === null) {
			throw new \InvalidArgumentException('Can not update a provider without an id');
		}
		$this->encryptClientSecret($provider);
		return $this->providerMapper->update($provider);
	}

	public function deleteProvider(int $id): void {
		$provider = $this->providerMapper->get($id);
		if ($provider !== null) {
			$this->providerMapper->delete($provider);
		}
	}

	/**
	 * Encrypt the plaintext client secret set by validation, if one was supplied.
	 */
	private function encryptClientSecret(OidcProvider $provider): void {
		$secret = $provider->getClientSecret();
		if ($secret !== null && $secret !== '') {
			$provider->setClientSecret($this->crypto->encrypt($secret));
		}
	}

	/**
	 * Extract the domain part of the account's email address, lower-cased.
	 */
	private function getEmailDomain(Account $account): ?string {
		$email = $account->getEmail();
		$atPos = strrpos($email, '@');
		if ($atPos === false || $atPos === strlen($email) - 1) {
			return null;
		}
		return mb_strtolower(substr($email, $atPos + 1));
	}

	/**
	 * Resolve the admin-configured provider responsible for this account's
	 * email domain, if any.
	 */
	public function getProviderForAccount(Account $account): ?OidcProvider {
		$domain = $this->getEmailDomain($account);
		if ($domain === null) {
			return null;
		}
		return $this->providerMapper->findByEmailDomain($domain);
	}

	/**
	 * Whether this account authenticates over XOAUTH2 against a configured OIDC provider.
	 */
	public function isOidcAccount(Account $account): bool {
		if ($account->getMailAccount()->getAuthMethod() !== 'xoauth2') {
			return false;
		}
		return $this->getProviderForAccount($account) !== null;
	}

	/**
	 * Fetch and cache the provider's OpenID Connect discovery document. The returned
	 * array is guaranteed to contain non-empty `authorization_endpoint` and
	 * `token_endpoint` entries.
	 *
	 * @return array<string, mixed>
	 * @throws Exception when the document can not be fetched or is missing endpoints
	 */
	public function getDiscovery(OidcProvider $provider): array {
		$discoveryUrl = $provider->getDiscoveryUrl();
		$cached = $this->discoveryCache->get($discoveryUrl);
		if (is_string($cached)) {
			/** @var array<string, mixed> $data */
			$data = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
			return $data;
		}

		$httpClient = $this->clientService->newClient();
		$response = $httpClient->get($discoveryUrl, [
			'headers' => ['Accept' => 'application/json'],
		]);
		/** @var array $data */
		$data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

		if (empty($data['authorization_endpoint']) || empty($data['token_endpoint'])) {
			throw new Exception('OIDC discovery document for ' . $discoveryUrl . ' is missing required endpoints');
		}

		$this->discoveryCache->set($discoveryUrl, json_encode($data, JSON_THROW_ON_ERROR), self::DISCOVERY_CACHE_TTL);
		return $data;
	}

	/**
	 * Resolve the provider's authorization and token endpoints, either from the
	 * admin-entered manual values or from its discovery document.
	 *
	 * @return array{authorization_endpoint: string, token_endpoint: string}
	 * @throws Exception when discovery is needed but can not be resolved
	 */
	public function getEndpoints(OidcProvider $provider): array {
		if ($provider->getManualEndpoints()) {
			return [
				'authorization_endpoint' => $provider->getAuthorizationEndpoint(),
				'token_endpoint' => $provider->getTokenEndpoint(),
			];
		}

		$discovery = $this->getDiscovery($provider);
		return [
			'authorization_endpoint' => (string)$discovery['authorization_endpoint'],
			'token_endpoint' => (string)$discovery['token_endpoint'],
		];
	}

	/**
	 * Exchange an authorization code for tokens and store them on the account.
	 *
	 * @param string $code the authorization code returned to the redirect URI
	 * @param string|null $codeVerifier the PKCE verifier, when PKCE was used
	 */
	public function finishConnect(OidcProvider $provider, Account $account, string $code, ?string $codeVerifier = null): Account {
		try {
			$tokenEndpoint = $this->getEndpoints($provider)['token_endpoint'];
		} catch (Exception $e) {
			$this->logger->error('Could not resolve OIDC endpoints for provider {providerId}: ' . $e->getMessage(), [
				'exception' => $e,
				'providerId' => $provider->getId(),
			]);
			return $account;
		}

		$body = [
			'client_id' => $provider->getClientId(),
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->getRedirectUrl(),
			'code' => $code,
		];
		$clientSecret = $this->getClientSecret($provider);
		if ($clientSecret !== null) {
			$body['client_secret'] = $clientSecret;
		}
		if ($codeVerifier !== null) {
			$body['code_verifier'] = $codeVerifier;
		}

		$httpClient = $this->clientService->newClient();
		try {
			$response = $httpClient->post($tokenEndpoint, [
				'headers' => ['Accept' => 'application/json'],
				'body' => $body,
			]);
		} catch (Exception $e) {
			$this->logger->error('Could not link OIDC account: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return $account;
		}

		$data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		if (isset($data['refresh_token'])) {
			$account->getMailAccount()->setOauthRefreshToken($this->crypto->encrypt($data['refresh_token']));
		}
		$account->getMailAccount()->setOauthAccessToken($this->crypto->encrypt($data['access_token']));
		$account->getMailAccount()->setOauthTokenTtl($this->timeFactory->getTime() + $data['expires_in']);
		return $account;
	}

	/**
	 * Refresh the account's access token using the stored refresh token, if it is
	 * about to expire. Safe to call outside a user session (cron).
	 */
	public function refresh(Account $account): Account {
		$oauthRefreshToken = $account->getMailAccount()->getOauthRefreshToken();
		if ($account->getMailAccount()->getOauthTokenTtl() === null || $oauthRefreshToken === null) {
			// Account is not authorized yet
			return $account;
		}

		// Only refresh if the token expires in the next minute
		if ($this->timeFactory->getTime() <= ($account->getMailAccount()->getOauthTokenTtl() - 60)) {
			// No need to refresh yet
			return $account;
		}

		$provider = $this->getProviderForAccount($account);
		if ($provider === null) {
			$this->logger->warning('Can not refresh OIDC token for account {accountId}: no provider matches its email domain', [
				'accountId' => $account->getId(),
			]);
			return $account;
		}

		try {
			$tokenEndpoint = $this->getEndpoints($provider)['token_endpoint'];
		} catch (Exception $e) {
			$this->logger->warning('Could not resolve OIDC endpoints for provider {providerId}: ' . $e->getMessage(), [
				'exception' => $e,
				'providerId' => $provider->getId(),
			]);
			return $account;
		}

		$body = [
			'client_id' => $provider->getClientId(),
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->crypto->decrypt($oauthRefreshToken),
		];
		$clientSecret = $this->getClientSecret($provider);
		if ($clientSecret !== null) {
			$body['client_secret'] = $clientSecret;
		}

		$httpClient = $this->clientService->newClient();
		try {
			$response = $httpClient->post($tokenEndpoint, [
				'headers' => ['Accept' => 'application/json'],
				'body' => $body,
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
		$account->getMailAccount()->setOauthTokenTtl($this->timeFactory->getTime() + $data['expires_in']);
		// Providers may rotate refresh tokens
		if (isset($data['refresh_token'])) {
			$account->getMailAccount()->setOauthRefreshToken($this->crypto->encrypt($data['refresh_token']));
		}

		return $account;
	}

	/**
	 * Decrypt the provider's stored client secret, if one is set.
	 */
	private function getClientSecret(OidcProvider $provider): ?string {
		$encrypted = $provider->getClientSecret();
		if ($encrypted === null || $encrypted === '') {
			return null;
		}
		return $this->crypto->decrypt($encrypted);
	}

	public function getRedirectUrl(): string {
		return $this->urlGenerator->linkToRouteAbsolute('mail.oidcIntegration.oauthRedirect');
	}
}
