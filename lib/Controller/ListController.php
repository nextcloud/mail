<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Exception;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ListController extends Controller {
	private MailManager $mailManager;
	private AccountService $accountService;
	private IClientService $httpClientService;
	private LoggerInterface $logger;
	private ?string $currentUserId;

	public function __construct(IRequest $request,
		MailManager $mailManager,
		AccountService $accountService,
		IClientService $httpClientService,
		LoggerInterface $logger,
		?string $userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->mailManager = $mailManager;
		$this->accountService = $accountService;
		$this->request = $request;
		$this->httpClientService = $httpClientService;
		$this->logger = $logger;
		$this->currentUserId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=10, period=3600)
	 */
	public function unsubscribe(int $id): JsonResponse {
		if ($this->currentUserId === null) {
			return JsonResponse::fail([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail(null, Http::STATUS_NOT_FOUND);
		}

		try {
			$imapMessage = $this->mailManager->getImapMessage(
				$account,
				$mailbox,
				$message->getUid(),
				true
			);
			$unsubscribeUrl = $imapMessage->getUnsubscribeUrl();
			if ($unsubscribeUrl === null || !$imapMessage->isOneClickUnsubscribe()) {
				return JsonResponse::fail(null, Http::STATUS_FORBIDDEN);
			}

			$httpClient = $this->httpClientService->newClient();
			$httpClient->post($unsubscribeUrl, [
				'body' => [
					'List-Unsubscribe' => 'One-Click'
				]
			]);
		} catch (Exception $e) {
			$this->logger->error('Could not unsubscribe mailing list', [
				'exception' => $e,
			]);
			return JsonResponse::error('Unknown error');
		}

		return JsonResponse::success();
	}
}
