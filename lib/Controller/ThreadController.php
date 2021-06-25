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
use Psr\Log\LoggerInterface;

class ThreadController extends Controller {

	/** @var string */
	private $currentUserId;

	/** @var LoggerInterface */
	private $logger;

	/** @var AccountService */
	private $accountService;

	/** @var IMailManager */
	private $mailManager;

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
	 * @param int[] $ids
	 * @param int $destMailboxId
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function move(array $ids, int $destMailboxId): JSONResponse {
		$batch = [];
		$dstMailbox = $this->mailManager->getMailbox($this->currentUserId, $destMailboxId);
		$dstAccount = $this->accountService->find($this->currentUserId, $dstMailbox->getAccountId());
		try {
			foreach ($ids as $id) {
				$message = $this->mailManager->getMessage($this->currentUserId, $id);
				$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
				$mailboxName = $mailbox->getName();
				$accountId = $mailbox->getAccountId();
				if (!array_key_exists($accountId, $batch)) {
					$batch[$accountId] = [];
				}
				if (!array_key_exists($mailboxName, $batch[$accountId])) {
					$batch[$accountId][$mailboxName] = [];
				}
				array_push($batch[$accountId][$mailboxName],$message);
			}
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		// Move threads from batch
		foreach ($batch as $accountId => $subbatch) {
			foreach ($subbatch as $mailboxName => $messages) {
				foreach ($messages as $message) {
					$srcMailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
					$srcAccount = $this->accountService->find($this->currentUserId, $srcMailbox->getAccountId());
					$this->mailManager->moveThread(
						$srcAccount,
						$srcMailbox,
						$dstAccount,
						$dstMailbox,
						$message->getThreadRootId()
					);
				}
			}
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int[] $ids
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function delete(array $ids): JSONResponse {
		// Creates a deletion batch subdivised in accounts and mailboxes
		$batch = [];
		try {
			foreach ($ids as $id) {
				$message = $this->mailManager->getMessage($this->currentUserId, $id);
				$mailbox = $this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId());
				$mailboxName = $mailbox->getName();
				$accountId = $mailbox->getAccountId();
				if (!array_key_exists($accountId, $batch)) {
					$batch[$accountId] = [];
				}
				if (!array_key_exists($mailboxName, $batch[$accountId])) {
					$batch[$accountId][$mailboxName] = [];
				}
				array_push($batch[$accountId][$mailboxName],$message);
			}
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}

		// Deletes threads from batch
		foreach ($batch as $accountId => $subbatch) {
			foreach ($subbatch as $mailboxName => $messages) {
				foreach ($messages as $message) {
					$this->mailManager->deleteThread(
						$this->accountService->find($this->currentUserId, $accountId),
						$this->mailManager->getMailbox($this->currentUserId, $message->getMailboxId()),
						$message->getThreadRootId()
					);
				};
			};
		};

		return new JSONResponse();
	}
}
