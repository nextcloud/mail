<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ListController extends Controller {
	private IMailManager $mailManager;
	private AccountService $accountService;
	private IMAPClientFactory $clientFactory;
	private IClientService $httpClientService;
	private LoggerInterface $logger;
	private ?string $currentUserId;

	public function __construct(IRequest $request,
		IMailManager $mailManager,
		AccountService $accountService,
		IMAPClientFactory $clientFactory,
		IClientService $httpClientService,
		LoggerInterface $logger,
		?string $userId) {
		parent::__construct(Application::APP_ID, $request);
		$this->mailManager = $mailManager;
		$this->accountService = $accountService;
		$this->clientFactory = $clientFactory;
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
