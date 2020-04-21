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

use Horde_Imap_Client;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Sync\SyncService;
use function base64_decode;
use function is_array;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\NotImplemented;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class FoldersController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var string */
	private $currentUserId;

	/**  @var IMailManager */
	private $mailManager;

	/** @var SyncService */
	private $syncService;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string $UserId
	 * @param IMailManager $mailManager
	 * @param SyncService $syncService
	 */
	public function __construct(string $appName,
								IRequest $request,
								AccountService $accountService,
								$UserId,
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
	 * @TrapError
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function index(int $accountId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		$folders = $this->mailManager->getFolders($account);
		return new JSONResponse([
			'id' => $accountId,
			'email' => $account->getEmail(),
			'folders' => $folders,
			'delimiter' => reset($folders)->getDelimiter(),
		]);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $syncToken
	 * @param int[] $uids
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function sync(int $accountId, string $folderId, array $uids = [], bool $init = false, string $query = null): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		if (empty($accountId) || empty($folderId) || !is_array($uids)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		try {
			$syncResponse = $this->syncService->syncMailbox(
				$account,
				base64_decode($folderId),
				Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
				array_map(function ($uid) {
					return (int) $uid;
				}, $uids),
				!$init,
				$query
			);
		} catch (MailboxNotCachedException $e) {
			return new JSONResponse(null, Http::STATUS_PRECONDITION_REQUIRED);
		} catch (IncompleteSyncException $e) {
			return \OCA\Mail\Http\JsonResponse::fail([], Http::STATUS_ACCEPTED);
		}

		return new JSONResponse($syncResponse);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function clearCache(int $accountId, string $folderId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		if (empty($accountId) || empty($folderId)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$this->syncService->clearCache($account, base64_decode($folderId));
		return new JSONResponse(null);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function markAllAsRead(int $accountId, string $folderId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		if (empty($accountId) || empty($folderId)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$syncResponse = $this->mailManager->markFolderAsRead($account, base64_decode($folderId));

		return new JSONResponse($syncResponse);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $folderId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function stats(int $accountId, string $folderId): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		if (empty($accountId) || empty($folderId)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$stats = $this->mailManager->getFolderStats($account, base64_decode($folderId));

		return new JSONResponse($stats);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function show() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 */
	public function update() {
		throw new NotImplemented();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function create(int $accountId, string $name) {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		return new JSONResponse($this->mailManager->createFolder($account, $name));
	}
}
