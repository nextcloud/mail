<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\InvalidOauthStateException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OauthStateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class MicrosoftIntegrationController extends Controller {
	private ?string $userId;
	private AccountService $accountService;
	private MicrosoftIntegration $microsoftIntegration;
	private LoggerInterface $logger;
	private OauthStateService $oauthStateService;

	public function __construct(IRequest $request,
		?string $userId,
		AccountService $accountService,
		MicrosoftIntegration $microsoftIntegration,
		LoggerInterface $logger,
		OauthStateService $oauthStateService) {
		parent::__construct(Application::APP_ID, $request);
		$this->userId = $userId;
		$this->accountService = $accountService;
		$this->microsoftIntegration = $microsoftIntegration;
		$this->logger = $logger;
		$this->oauthStateService = $oauthStateService;
	}

	/**
	 * @param string|null $tenantId
	 * @param string $clientId
	 * @param string $clientSecret
	 *
	 * @return JsonResponse
	 */
	public function configure(?string $tenantId, string $clientId, string $clientSecret): JsonResponse {
		if (empty($clientId) || empty($clientSecret)) {
			return JsonResponse::fail(null, Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$this->microsoftIntegration->configure(
			$tenantId,
			$clientId,
			$clientSecret,
		);

		return JsonResponse::success([
			'clientId' => $clientId,
		]);
	}

	/*
	 * @return JsonResponse
	 */
	public function unlink(): JsonResponse {
		$this->microsoftIntegration->unlink();

		return JsonResponse::success([]);
	}

	/**
	 * @param string|null $code
	 * @param string|null $state
	 * @param string|null $session_state
	 * @param string|null $error
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function oauthRedirect(?string $code, ?string $state, ?string $session_state, ?string $error): Response {
		if ($this->userId === null) {
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		if (!isset($code, $state)) {
			// TODO: handle error
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}
		try {
			$accountId = $this->oauthStateService->validateAndConsume($state, $this->userId);
			$account = $this->accountService->find($this->userId, $accountId);
		} catch (InvalidOauthStateException|ClientException $e) {
			$this->logger->warning('Cannot link Microsoft account: invalid OAuth state', [
				'exception' => $e,
			]);
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		$updated = $this->microsoftIntegration->finishConnect(
			$account,
			$code,
		);
		$this->accountService->update($updated->getMailAccount());

		return new StandaloneTemplateResponse(
			Application::APP_ID,
			'oauth_done',
			[],
			'guest',
		);
	}
}
