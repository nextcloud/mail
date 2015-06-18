<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Account;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;

class FoldersController extends Controller {

	/** @var AccountService */
	private $accountService;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param $mailAccountMapper
	 * @param $currentUserId
	 */
	public function __construct($appName, $request, $accountService, $currentUserId){
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $currentUserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$account = $this->getAccount();
		$json = $account->getListArray();

		$folders = array_filter($json['folders'], function($folder){
			return is_null($folder['parent']);
		});
		foreach($json['folders'] as $folder) {
			if (is_null($folder['parent'])) {
				continue;
			}
			$parentId = $folder['parent'];
			foreach($folders as &$parent) {
				if($parent['id'] === $parentId) {
					if (!isset($parent['folders'])) {
						$parent['folders'] = [];
					}
					$parent['folders'][] = $folder;
					break;
				}
			}
		}

		$json['folders'] = array_values($folders);

		return new JSONResponse($json);
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
	 * @param string $folderId
	 * @return JSONResponse
	 */
	public function destroy($folderId) {
		try {
			$account = $this->getAccount();
			$account = new Account($account);
			$imap = $account->getImapConnection();
			$imap->deleteMailbox($folderId);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function create() {
		try {
			$mailbox = $this->params('mailbox');
			$account = $this->getAccount();
			$account = new Account($account);
			$imap = $account->getImapConnection();

			// TODO: read http://tools.ietf.org/html/rfc6154
			$imap->createMailbox($mailbox);

			$newFolderId = $mailbox;
			return new JSONResponse(
				['data' => ['id' => $newFolderId]],
				Http::STATUS_CREATED);
		} catch (\Horde_Imap_Client_Exception $e) {
			$response = new JSONResponse();
			$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			return $response;
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * @NoAdminRequired
	 * @param $folders
	 * @return JSONResponse
	 */
	public function detectChanges($folders) {
		try {
			$query = [];
			foreach($folders as $folder) {
				$folderId = base64_decode($folder['id']);
				$parts = explode('/', $folderId);
				if (count($parts) > 1 && $parts[1] === 'FLAGGED') {
					continue;
				}
				if (isset($folder['error'])) {
					continue;
				}
				$query[$folderId] = $folder;
			}
			$account = $this->getAccount();
			$mailBoxes = $account->getChangedMailboxes($query);

			return new JSONResponse($mailBoxes);
		} catch (\Horde_Imap_Client_Exception $e) {
			$response = new JSONResponse();
			$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			return $response;
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * TODO: private functions below have to be removed from controller -> imap service to be build
	 */
	private function getAccount() {
		$accountId = $this->params('accountId');
		return $this->accountService->find($this->currentUserId, $accountId);
	}
}
