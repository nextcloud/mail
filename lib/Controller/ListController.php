<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use Exception;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ListController extends Controller {
	private readonly IClientService $httpClientService;

	public function __construct(
		IRequest $request,
		private readonly IMailManager $mailManager,
		private readonly AccountService $accountService,
		private readonly IMAPClientFactory $clientFactory,
		IClientService $httpClientService,
		private readonly LoggerInterface $logger,
		private readonly ?string $currentUserId
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->request = $request;
		$this->httpClientService = $httpClientService;
	}

	/**
	 * @NoAdminRequired
	 * @UserRateThrottle(limit=10, period=3600)
	 */
	public function unsubscribe(int $id): JsonResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return JsonResponse::fail(null, Http::STATUS_NOT_FOUND);
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$imapMessage = $this->mailManager->getImapMessage(
				$client,
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
		} finally {
			$client->logout();
		}

		return JsonResponse::success();
	}
}
