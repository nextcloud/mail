<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use Horde_Imap_Client;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class MailboxesController extends Controller {
	private AccountService $accountService;
	private ?string $currentUserId;
	private IMailManager $mailManager;
	private SyncService $syncService;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string|null $UserId
	 * @param IMailManager $mailManager
	 * @param SyncService $syncService
	 */
	public function __construct(string $appName,
		IRequest $request,
		AccountService $accountService,
		?string $userId,
		IMailManager $mailManager,
		SyncService $syncService) {
		parent::__construct($appName, $request);

		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->mailManager = $mailManager;
		$this->syncService = $syncService;
	}

	/**
	 *
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function index(int $accountId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		$mailboxes = $this->mailManager->getMailboxes($account);
		return new JSONResponse([
			'id' => $accountId,
			'email' => $account->getEmail(),
			'mailboxes' => $mailboxes,
			'delimiter' => reset($mailboxes)->getDelimiter(),
		]);
	}

	/**
	 *
	 * @param int $id
	 * @param string $name
	 * @return JSONResponse
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function patch(int $id,
		?string $name = null,
		?bool $subscribed = null,
		?bool $syncInBackground = null): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		if ($name !== null) {
			$mailbox = $this->mailManager->renameMailbox(
				$account,
				$mailbox,
				$name
			);
		}
		if ($subscribed !== null) {
			$mailbox = $this->mailManager->updateSubscription(
				$account,
				$mailbox,
				$subscribed
			);
		}
		if ($syncInBackground !== null) {
			$mailbox = $this->mailManager->enableMailboxBackgroundSync(
				$mailbox,
				$syncInBackground
			);
		}

		return new JSONResponse($mailbox);
	}

	/**
	 *
	 * @param int $id
	 * @param int[] $ids
	 *
	 * @param bool $init
	 * @param string|null $query
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function sync(int $id, array $ids = [], ?int $lastMessageTimestamp = null, bool $init = false, string $sortOrder = 'newest', ?string $query = null): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());
		$order = $sortOrder === 'newest' ? IMailSearch::ORDER_NEWEST_FIRST: IMailSearch::ORDER_OLDEST_FIRST;

		try {
			$syncResponse = $this->syncService->syncMailbox(
				$account,
				$mailbox,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
				!$init,
				$lastMessageTimestamp,
				array_map(static function ($id) {
					return (int)$id;
				}, $ids),
				$order,
				$query
			);
		} catch (MailboxNotCachedException $e) {
			return new JSONResponse([], Http::STATUS_PRECONDITION_REQUIRED);
		} catch (IncompleteSyncException $e) {
			return \OCA\Mail\Http\JsonResponse::fail([], Http::STATUS_ACCEPTED);
		}

		return new JSONResponse($syncResponse);
	}

	/**
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function clearCache(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->syncService->clearCache($account, $mailbox);
		return new JSONResponse([]);
	}

	/**
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function markAllAsRead(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->markFolderAsRead($account, $mailbox);

		return new JSONResponse([]);
	}

	/**
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function stats(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		return new JSONResponse($mailbox->getStats());
	}

	/**
	 *
	 * @return never
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function show() {
		throw new NotImplemented();
	}

	/**
	 *
	 * @return never
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function update() {
		throw new NotImplemented();
	}

	/**
	 *
	 *
	 * @return JSONResponse
	 * @throws ServiceException
	 * @throws ClientException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function create(int $accountId, string $name): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		return new JSONResponse($this->mailManager->createMailbox($account, $name));
	}

	/**
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function destroy(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->deleteMailbox($account, $mailbox);
		return new JSONResponse();
	}

	/**
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 */
	#[TrapError]
	#[NoAdminRequired]
	public function clearMailbox(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->clearMailbox($account, $mailbox);
		return new JSONResponse();
	}
}
