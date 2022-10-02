<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ThreadController extends Controller {
	private string $currentUserId;
	private AccountService $accountService;
	private IMailManager $mailManager;

	public function __construct(string $appName,
								IRequest $request,
								string $UserId,
								AccountService $accountService,
								IMailManager $mailManager) {
		parent::__construct($appName, $request);

		$this->currentUserId = $UserId;
		$this->accountService = $accountService;
		$this->mailManager = $mailManager;
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param int $destMailboxId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
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
	 * @TrapError
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
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
