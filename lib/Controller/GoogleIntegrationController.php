<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;



use function filter_var;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class GoogleIntegrationController extends Controller {
	private ?string $userId;
	private GoogleIntegration $googleIntegration;
	private AccountService $accountService;
	private LoggerInterface $logger;
	private MailboxSync $mailboxSync;


	public function __construct(IRequest $request,
		?string $UserId,
		GoogleIntegration $googleIntegration,
		AccountService $accountService,
		LoggerInterface $logger,
		MailboxSync $mailboxSync) {
		parent::__construct(Application::APP_ID, $request);
		$this->userId = $UserId;
		$this->googleIntegration = $googleIntegration;
		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->mailboxSync = $mailboxSync;
	}

	/**
	 * @param string $clientId
	 * @param string $clientSecret
	 *
	 * @return JsonResponse
	 */
	public function configure(string $clientId, string $clientSecret): JsonResponse {
		if (empty($clientId) || empty($clientSecret)) {
			return JsonResponse::fail(null, Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		$this->googleIntegration->configure(
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
		$this->googleIntegration->unlink();

		return JsonResponse::success([]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function oauthRedirect(?string $code, ?string $state, ?string $scope, ?string $error): Response {
		if ($this->userId === null) {
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		if (!isset($code, $state, $scope)) {
			// TODO: handle error
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}
		if (!filter_var($state, FILTER_VALIDATE_INT)) {
			$this->logger->warning('Can not link Google account due to invalid state/account id {state}', [
				'state' => $state,
			]);
			// TODO: redirect to main nextcloud page
			return new StandaloneTemplateResponse(
				Application::APP_ID,
				'oauth_done',
				[],
				'guest',
			);
		}

		try {
			$account = $this->accountService->find(
				$this->userId,
				(int)$state,
			);
		} catch (ClientException $e) {
			$this->logger->warning('Attempted Google authentication redirect for account: ' . $e->getMessage(), [
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

		$updated = $this->googleIntegration->finishConnect(
			$account,
			$code,
		);
		$this->accountService->update($updated->getMailAccount());
		try {
			$this->mailboxSync->sync($account, $this->logger);
		} catch (ServiceException $e) {
			$this->logger->error('Failed syncing the newly created account' . $e->getMessage(), [
				'exception' => $e,
			]);
		}
		return new StandaloneTemplateResponse(
			Application::APP_ID,
			'oauth_done',
			[],
			'guest',
		);
	}
}
