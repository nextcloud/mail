<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AiIntegrations\AiIntegrationsService;
use OCA\Mail\Service\DelegationService;
use OCA\Mail\Service\MailManager;
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
	public function __construct(
		string $appName,
		IRequest $request,
		private string $userId,
		private AccountService $accountService,
		private MailManager $mailManager,
		private SnoozeService $snoozeService,
		private AiIntegrationsService $aiIntergrationsService,
		private LoggerInterface $logger,
		private DelegationService $delegationService,
	) {
		parent::__construct($appName, $request);
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$srcAccount = $this->accountService->find($effectiveUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($effectiveUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($effectiveUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$threadRootId = $message->getThreadRootId();
		if ($threadRootId === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$this->mailManager->moveThread(
			$srcAccount,
			$srcMailbox,
			$dstAccount,
			$dstMailbox,
			$threadRootId
		);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId moved thread <$id> to mailbox <$destMailboxId> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$selectedMessage = $this->mailManager->getMessage($effectiveUserId, $id);
			$srcMailbox = $this->mailManager->getMailbox($effectiveUserId, $selectedMessage->getMailboxId());
			$srcAccount = $this->accountService->find($effectiveUserId, $srcMailbox->getAccountId());
			$dstMailbox = $this->mailManager->getMailbox($effectiveUserId, $destMailboxId);
			$dstAccount = $this->accountService->find($effectiveUserId, $dstMailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->snoozeThread($selectedMessage, $unixTimestamp, $srcAccount, $srcMailbox, $dstAccount, $dstMailbox);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId snoozed thread <$id> until <$unixTimestamp> in mailbox <$destMailboxId> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$selectedMessage = $this->mailManager->getMessage($effectiveUserId, $id);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->snoozeService->unSnoozeThread($selectedMessage, $effectiveUserId);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId unsnoozed thread <$id> on behalf of $effectiveUserId");

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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$threadRootId = $message->getThreadRootId();
		if ($threadRootId === null || $threadRootId === '') {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$thread = $this->mailManager->getThread($account, $threadRootId);
		try {
			$summary = $this->aiIntergrationsService->summarizeThread(
				$account,
				$threadRootId,
				$thread,
				$this->userId,
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$threadRootId = $message->getThreadRootId();
		if ($threadRootId === null || $threadRootId === '') {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$thread = $this->mailManager->getThread($account, $threadRootId);
		$data = $this->aiIntergrationsService->generateEventData(
			$account,
			$threadRootId,
			$thread,
			$this->userId,
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
			$effectiveUserId = $this->delegationService->resolveMessageUserId($id, $this->userId);
			$message = $this->mailManager->getMessage($effectiveUserId, $id);
			$mailbox = $this->mailManager->getMailbox($effectiveUserId, $message->getMailboxId());
			$account = $this->accountService->find($effectiveUserId, $mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		$threadRootId = $message->getThreadRootId();
		if ($threadRootId === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
		$this->mailManager->deleteThread(
			$account,
			$mailbox,
			$threadRootId
		);
		$this->delegationService->logDelegatedAction($this->userId, $effectiveUserId, "$this->userId deleted thread <$id> on behalf of $effectiveUserId");

		return new JSONResponse();
	}
}
