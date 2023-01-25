<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Http\TrapError;
use Horde_Imap_Client;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

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
								?string $UserId,
								IMailManager $mailManager,
								SyncService $syncService) {
		parent::__construct($appName, $request);

		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->mailManager = $mailManager;
		$this->syncService = $syncService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
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
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $name
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
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
	 * @NoAdminRequired
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
	public function sync(int $id, array $ids = [], bool $init = false, string $query = null): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		try {
			$syncResponse = $this->syncService->syncMailbox(
				$account,
				$mailbox,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
				array_map(static function ($id) {
					return (int)$id;
				}, $ids),
				!$init,
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
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function clearCache(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->syncService->clearCache($account, $mailbox);
		return new JSONResponse([]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function markAllAsRead(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->markFolderAsRead($account, $mailbox);

		return new JSONResponse([]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function stats(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		return new JSONResponse($mailbox->getStats());
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @return never
	 */
	#[TrapError]
	public function show() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @return never
	 */
	#[TrapError]
	public function update() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @return JSONResponse
	 * @throws ServiceException
	 * @throws ClientException
	 */
	#[TrapError]
	public function create(int $accountId, string $name): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		return new JSONResponse($this->mailManager->createMailbox($account, $name));
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
	public function destroy(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->deleteMailbox($account, $mailbox);
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
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 */
	#[TrapError]
	public function clearMailbox(int $id): JSONResponse {
		$mailbox = $this->mailManager->getMailbox($this->currentUserId, $id);
		$account = $this->accountService->find($this->currentUserId, $mailbox->getAccountId());

		$this->mailManager->clearMailbox($account, $mailbox);
		return new JSONResponse();
	}
}
