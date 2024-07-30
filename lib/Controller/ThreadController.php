<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\SnoozeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ThreadController extends Controller {
	private string $currentUserId;
	private AccountService $accountService;
	private IMailManager $mailManager;
	private SnoozeService $snoozeService;
	private AiIntegrationsService $aiIntergrationsService;
	private LoggerInterface $logger;


	public function __construct(string $appName,
		IRequest $request,
		string $UserId,
		AccountService $accountService,
		IMailManager $mailManager,
		SnoozeService $snoozeService,
		AiIntegrationsService $aiIntergrationsService,
		LoggerInterface $logger) {
		parent::__construct($appName, $request);
		$this->currentUserId = $UserId;
		$this->accountService = $accountService;
		$this->mailManager = $mailManager;
		$this->snoozeService = $snoozeService;
		$this->aiIntergrationsService = $aiIntergrationsService;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param int $destMailboxId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function move(int $id, int $destMailboxId): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$srcAccount = $this->accountService->find($this->currentUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($this->currentUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($this->currentUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->moveThread(
			$srcAccount,
			$srcMailbox,
			$dstAccount,
			$dstMailbox,
			$message->getThreadRootId()
		);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param int $unixTimestamp
	 * @param int $destMailboxId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function snooze(int $id, int $unixTimestamp, int $destMailboxId): JSONResponse {
		try {
			$selectedMessage = $this->mailManager->getMessage($this->currentUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($this->currentUserId, $selectedMessage->getMailboxId());
			$srcAccount = $this->accountService->find($this->currentUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($this->currentUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($this->currentUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->snoozeThread($selectedMessage, $unixTimestamp, $srcAccount, $srcMailbox, $dstAccount, $dstMailbox);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function unSnooze(int $id): JSONResponse {
		try {
			$selectedMessage = $this->mailManager->getMessage($this->currentUserId, $id);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->unSnoozeThread($selectedMessage, $this->currentUserId);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 */
	public function summarize(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		if (empty($message->getThreadRootId())) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$thread = $this->mailManager->getThread($account, $message->getThreadRootId());
		try {
			$summary = $this->aiIntergrationsService->summarizeThread(
				$account,
				$message->getThreadRootId(),
				$thread,
				$this->currentUserId,
			);
		} catch (\Throwable $e) {
			$this->logger->error('Summarizing thread failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return new JSONResponse([], Http::STATUS_NO_CONTENT);
		}

		return new JSONResponse(['data' => $summary]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function generateEventData(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		if (empty($message->getThreadRootId())) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$thread = $this->mailManager->getThread($account, $message->getThreadRootId());
		$data = $this->aiIntergrationsService->generateEventData(
			$account,
			$message->getThreadRootId(),
			$thread,
			$this->currentUserId,
		);

		return new JSONResponse(['data' => $data]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function delete(int $id): JSONResponse {
		try {
			$message = $this->mailManager->getMessage($this->currentUserId, $id);
			$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
			$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->deleteThread(
			$account,
			$mailbox,
			$message->getThreadRootId()
		);

		return new JSONResponse();
	}
}
