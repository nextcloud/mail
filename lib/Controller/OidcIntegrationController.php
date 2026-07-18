<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\InvalidOauthStateException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\ValidationException;
use OCA\Mail\Http\JsonResponse as HttpJsonResponse;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Integration\OidcIntegration;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OauthStateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class OidcIntegrationController extends Controller {
	public function __construct(
		IRequest $request,
		private ?string $userId,
		private OidcIntegration $oidcIntegration,
		private AccountService $accountService,
		private LoggerInterface $logger,
		private MailboxSync $mailboxSync,
		private OauthStateService $oauthStateService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List all configured OIDC providers (admin). Client secrets are masked.
	 */
	public function index(): JSONResponse {
		return new JSONResponse($this->oidcIntegration->getProviders());
	}

	public function create(array $data): JSONResponse {
		try {
			return new JSONResponse($this->oidcIntegration->createProvider($data));
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		} catch (\Exception $e) {
			return HttpJsonResponse::fail([$e->getMessage()]);
		}
	}

	public function update(int $id, array $data): JSONResponse {
		try {
			return new JSONResponse(
				$this->oidcIntegration->updateProvider(array_merge($data, ['id' => $id])),
			);
		} catch (ValidationException $e) {
			return HttpJsonResponse::fail([$e->getFields()]);
		} catch (\Exception $e) {
			return HttpJsonResponse::fail([$e->getMessage()]);
		}
	}

	public function destroy(int $id): JSONResponse {
		$this->oidcIntegration->deleteProvider($id);
		return new JSONResponse([]);
	}

	/**
	 * Start the interactive consent flow: resolve the provider's discovery document
	 * and redirect the popup to the IdP authorization endpoint. Opened as a top-level
	 * navigation, so it carries no CSRF token.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function authorize(int $providerId, string $state): Response {
		$provider = $this->oidcIntegration->getProvider($providerId);
		if ($provider === null) {
			$this->logger->warning('Cannot start OIDC consent flow: unknown provider {providerId}', [
				'providerId' => $providerId,
			]);
			return $this->done();
		}

		try {
			$url = $this->oidcIntegration->getAuthorizationUrl($provider, $state);
		} catch (\Exception $e) {
			$this->logger->error('Cannot start OIDC consent flow: ' . $e->getMessage(), [
				'exception' => $e,
				'providerId' => $providerId,
			]);
			return $this->done();
		}

		return new RedirectResponse($url);
	}

	/**
	 * OAuth authorization-code callback. Exchanges the code for tokens and stores
	 * them on the account matched by the CSRF state.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function oauthRedirect(?string $code, ?string $state, ?string $error): Response {
		if ($this->userId === null || !isset($code, $state)) {
			return $this->done();
		}

		try {
			$accountId = $this->oauthStateService->validateAndConsume($state, $this->userId);
			$account = $this->accountService->find($this->userId, $accountId);
		} catch (InvalidOauthStateException|ClientException $e) {
			$this->logger->warning('Cannot link OIDC account: invalid OAuth state', [
				'exception' => $e,
			]);
			return $this->done();
		}

		$provider = $this->oidcIntegration->getProviderForAccount($account);
		if ($provider === null) {
			$this->logger->warning('Cannot link OIDC account {accountId}: no provider matches its email domain', [
				'accountId' => $account->getId(),
			]);
			return $this->done();
		}

		$updated = $this->oidcIntegration->finishConnect($provider, $account, $code);
		$this->accountService->update($updated->getMailAccount());
		try {
			$this->mailboxSync->sync($account, $this->logger);
		} catch (ServiceException $e) {
			$this->logger->error('Failed syncing the newly linked OIDC account: ' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
		return $this->done();
	}

	private function done(): StandaloneTemplateResponse {
		return new StandaloneTemplateResponse(
			Application::APP_ID,
			'oauth_done',
			[],
			'guest',
		);
	}
}
