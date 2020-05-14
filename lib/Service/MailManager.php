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

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;

class MailManager implements IMailManager {

	/**
	 * https://tools.ietf.org/html/rfc3501#section-2.3.2
	 */
	private const ALLOWED_FLAGS = [
		'seen' => [Horde_Imap_Client::FLAG_SEEN],
		'answered' => [Horde_Imap_Client::FLAG_ANSWERED],
		'flagged' => [Horde_Imap_Client::FLAG_FLAGGED],
		'deleted' => [Horde_Imap_Client::FLAG_DELETED],
		'draft' => [Horde_Imap_Client::FLAG_DRAFT],
		'recent' => [Horde_Imap_Client::FLAG_RECENT],
		'junk' => [Horde_Imap_Client::FLAG_JUNK, 'junk'],
	];

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MailboxSync $mailboxSync,
								FolderMapper $folderMapper,
								MessageMapper $messageMapper,
								IEventDispatcher $eventDispatcher) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->messageMapper = $messageMapper;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @param Account $account
	 *
	 * @return Folder[]
	 * @throws ServiceException
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
	 * @param string $name
	 *
	 * @return Folder
	 * @throws ServiceException
	 */
	public function createFolder(Account $account, string $name): Folder {
		$client = $this->imapClientFactory->getClient($account);

		$folder = $this->folderMapper->createFolder($client, $account, $name);
		try {
			$this->folderMapper->getFoldersStatus([$folder], $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException("Could not get mailbox status: " . $e->getMessage(), $e->getCode(), $e);
		}
		$this->folderMapper->detectFolderSpecialUse([$folder]);

		$this->mailboxSync->sync($account, true);

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
				$mailbox->getName(),
				$id,
				$loadBody
			);
		} catch (Horde_Imap_Client_Exception|DoesNotExistException $e) {
			throw new ServiceException("Could not load message", $e->getCode(), $e);
		}
	}

	/**
	 * @param Account $account
	 * @param string $mb
	 * @param int $id
	 *
	 * @return string
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Account $account, string $mailbox, int $id): string {
		$client = $this->imapClientFactory->getClient($account);

		try {
			return $this->messageMapper->getSource(
				$client,
				$mailbox,
				$id
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
	 * @return void
	 * @throws ServiceException
	 *
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
			$sourceMailbox = $this->mailboxMapper->find($account, $mailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("Source mailbox $mailboxId does not exist", 0, $e);
		}
		try {
			$trashMailbox = $this->mailboxMapper->findSpecial($account, 'trash');
		} catch (DoesNotExistException $e) {
			throw new ServiceException("No trash folder", 0, $e);
		}

		if ($mailboxId === $trashMailbox->getName()) {
			// Delete inside trash -> expunge
			$this->messageMapper->expunge(
				$this->imapClientFactory->getClient($account),
				$sourceMailbox->getName(),
				$messageId
			);
		} else {
			$this->messageMapper->move(
				$this->imapClientFactory->getClient($account),
				$sourceMailbox->getName(),
				$messageId,
				$trashMailbox->getName()
			);
		}

		$this->eventDispatcher->dispatch(
			MessageDeletedEvent::class,
			new MessageDeletedEvent($account, $sourceMailbox, $messageId)
		);
	}

	/**
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
	 *
	 * @return void
	 * @throws ServiceException
	 *
	 */
	private function moveMessageOnSameAccount(Account $account,
											  string $sourceFolderId,
											  string $destFolderId,
											  int $messageId): void {
		$client = $this->imapClientFactory->getClient($account);

		$this->messageMapper->move($client, $sourceFolderId, $messageId, $destFolderId);
	}

	public function markFolderAsRead(Account $account, string $folderId): void {
		$client = $this->imapClientFactory->getClient($account);

		$this->messageMapper->markAllRead($client, $folderId);
	}

	public function flagMessage(Account $account, string $mailbox, int $uid, string $flag, bool $value): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$mb = $this->mailboxMapper->find($account, $mailbox);
		} catch (DoesNotExistException $e) {
			throw new ClientException("Mailbox $mailbox does not exist", 0, $e);
		}

		// Only send system flags to the IMAP server as other flags might not be supported
		$imapFlags = self::ALLOWED_FLAGS[$flag] ?? [];
		try {
			foreach ($imapFlags as $imapFlag) {
				if ($value) {
					$this->messageMapper->addFlag($client, $mb, $uid, $imapFlag);
				} else {
					$this->messageMapper->removeFlag($client, $mb, $uid, $imapFlag);
				}
			}
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException("Could not set message flag on IMAP: " . $e->getMessage(), $e->getCode(), $e);
		}

		$this->eventDispatcher->dispatch(
			MessageFlaggedEvent::class,
			new MessageFlaggedEvent(
				$account,
				$mb,
				$uid,
				$flag,
				$value
			)
		);
	}
}
