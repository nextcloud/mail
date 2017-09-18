<?php

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

use Horde_Imap_Client_Exception;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\IMAP\Sync\Request as SyncRequest;
use OCA\Mail\IMAP\Sync\Response as SyncResponse;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
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

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param string $UserId
	 * @param IMailManager $mailManager
	 */
	public function __construct($appName, IRequest $request,
		AccountService $accountService, $UserId, IMailManager $mailManager) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->mailManager = $mailManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $accountId
	 * @return JSONResponse
	 */
	public function index($accountId) {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		$folders = $this->mailManager->getFolders($account);
		return [
			'id' => $accountId,
			'email' => $account->getEmail(),
			'folders' => $folders,
			'delimiter' => reset($folders)->getDelimiter(),
		];
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $syncToken
	 * @param int[] $uids
	 * @return SyncResponse
	 */
	public function sync($accountId, $folderId, $syncToken, $uids = []) {
		$account = $this->accountService->find($this->currentUserId, $accountId);

		if (empty($accountId) || empty($folderId) || empty($syncToken) || !is_array($uids)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return $this->mailManager->syncMessages($account, new SyncRequest(base64_decode($folderId), $syncToken, $uids));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function show() {
		$response = new JSONResponse();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function update() {
		$response = new JSONResponse();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function create() {
		$response = new JSONResponse();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

}
