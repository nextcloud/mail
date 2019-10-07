<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_Sync;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\IMAP\Sync\Request;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\IMAP\Sync\Synchronizer;
use OCA\Mail\Model\IMAPMessage;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;

class MailManager implements IMailManager {

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var Synchronizer */
	private $synchronizer;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MailboxSync $mailboxSync,
								FolderMapper $folderMapper,
								Synchronizer $synchronizer,
								MessageMapper $messageMapper,
								IEventDispatcher $eventDispatcher) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->synchronizer = $synchronizer;
		$this->messageMapper = $messageMapper;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @param Account $account
	 *
	 * @return Folder[]
	 */
	public function getFolders(Account $account): array {
		$this->mailboxSync->sync($account);

		return array_map(
			function (Mailbox $mb) {
				return $mb->toFolder();
			},
			$this->mailboxMapper->findAll($account)
		);
	}

	/**
	 * @param Account $account
	 * @param Request $syncRequest
	 *
	 * @return Response
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function syncMessages(Account $account, Request $syncRequest): Response {
		$client = $this->imapClientFactory->getClient($account);

		try {
			return $this->synchronizer->sync($client, $syncRequest);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException("Could not sync messages", 0, $e);
		} catch (Horde_Imap_Client_Exception_Sync $e) {
			throw new ClientException("Sync failed because of an invalid sync token or UID validity changed", 0, $e);
		}
	}

	/**
	 * @param Account $account
	 * @param string $name
	 *
	 * @return Folder
	 */
	public function createFolder(Account $account, string $name): Folder {
		$client = $this->imapClientFactory->getClient($account);

		$folder = $this->folderMapper->createFolder($client, $account, $name);
		$this->folderMapper->getFoldersStatus([$folder], $client);
		$this->folderMapper->detectFolderSpecialUse([$folder]);
		return $folder;
	}

	/**
	 * @param Account $account
	 * @param string $folderId
	 *
	 * @return FolderStats
	 */
	public function getFolderStats(Account $account, string $folderId): FolderStats {
		$client = $this->imapClientFactory->getClient($account);

		return $this->folderMapper->getFoldersStatusAsObject($client, $folderId);
	}

	public function getMessage(Account $account, string $mailbox, int $id, bool $loadBody = false): IMAPMessage {
		$client = $this->imapClientFactory->getClient($account);
		$mailbox = $this->mailboxMapper->find($account, $mailbox);

		try {
			return $this->messageMapper->find(
				$client,
				$mailbox->getMailbox(),
				$id,
				$loadBody
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException("Could not load message", 0, $e);
		}
	}

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 *
	 * @throws ServiceException
	 */
	public function moveMessage(Account $sourceAccount,
								string $sourceFolderId,
								int $messageId,
								Account $destinationAccount,
								string $destFolderId) {
		if ($sourceAccount->getId() === $destinationAccount->getId()) {
			$this->moveMessageOnSameAccount(
				$sourceAccount,
				$sourceFolderId,
				$destFolderId,
				$messageId
			);
		} else {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
	}

	/**
	 * @throws ServiceException
	 * @todo evaluate if we should sync mailboxes first
	 *
	 */
	public function deleteMessage(Account $account,
								  string $mailboxId,
								  int $messageId): void {
		$this->eventDispatcher->dispatch(
			BeforeMessageDeletedEvent::class,
			new BeforeMessageDeletedEvent($account, $mailboxId, $messageId)
		);

		try {
			$sourceFolder = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("Source mailbox $mailboxId does not exist", 0, $e);
		}
		try {
			$trashFolder = $this->mailboxMapper->findSpecial($account, 'trash');
		} catch (DoesNotExistException $e) {
			throw new ServiceException("No trash folder", 0, $e);
		}

		if ($mailboxId === $trashFolder->getName()) {
			// Delete inside trash -> expunge
			$this->messageMapper->expunge(
				$this->imapClientFactory->getClient($account),
				$sourceFolder->getName(),
				$messageId
			);
		} else {
			$this->messageMapper->move(
				$this->imapClientFactory->getClient($account),
				$sourceFolder->getName(),
				$messageId,
				$trashFolder->getName()
			);
		}
	}

	/**
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
	 *
	 * @throws ServiceException
	 */
	private function moveMessageOnSameAccount(Account $account,
											  string $sourceFolderId,
											  string $destFolderId,
											  int $messageId) {
		$client = $this->imapClientFactory->getClient($account);

		$this->messageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
	}

	public function markFolderAsRead(Account $account, string $folderId): void {
		$client = $this->imapClientFactory->getClient($account);

		$this->messageMapper->markAllRead($client, $folderId);
	}

}
