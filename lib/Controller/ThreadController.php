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
use OCA\Mail\Service\DelegationService;
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
	private DelegationService $delegationService;


	public function __construct(string $appName,
		IRequest $request,
		string $userId,
		AccountService $accountService,
		IMailManager $mailManager,
		SnoozeService $snoozeService,
		AiIntegrationsService $aiIntergrationsService,
		LoggerInterface $logger,
		DelegationService $delegationService) {
		parent::__construct($appName, $request);
		$this->currentUserId = $userId;
		$this->accountService = $accountService;
		$this->mailManager = $mailManager;
		$this->snoozeService = $snoozeService;
		$this->aiIntergrationsService = $aiIntergrationsService;
		$this->logger = $logger;
		$this->delegationService = $delegationService;
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$srcAccount = $this->accountService->find($effectiveUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($effectiveUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($effectiveUserId, $dstMailbox->getAccountId());
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
		$this->delegationService->logDelegatedAction("$this->currentUserId moved thread <$id> to mailbox <$destMailboxId> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$selectedMessage = $this->mailManager->getMessage($effectiveUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($effectiveUserId, $selectedMessage->getMailboxId());
			$srcAccount = $this->accountService->find($effectiveUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($effectiveUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($effectiveUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->snoozeThread($selectedMessage, $unixTimestamp, $srcAccount, $srcMailbox, $dstAccount, $dstMailbox);
		$this->delegationService->logDelegatedAction("$this->currentUserId snoozed thread <$id> until <$unixTimestamp> in mailbox <$destMailboxId> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$selectedMessage = $this->mailManager->getMessage($effectiveUserId, $id);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->unSnoozeThread($selectedMessage, $effectiveUserId);
		$this->delegationService->logDelegatedAction("$this->currentUserId unsnoozed thread <$id> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->currentUserId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->mailManager->deleteThread(
			$account,
			$mailbox,
			$message->getThreadRootId()
		);
		$this->delegationService->logDelegatedAction("$this->currentUserId deleted thread <$id> on behalf of $effectiveUserId");

		return new JSONResponse();
	}
}
