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

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
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

	public function __construct(IMAPClientFactory $imapClientFactory,
								MailboxMapper $mailboxMapper,
								MailboxSync $mailboxSync,
								FolderMapper $folderMapper,
								Synchronizer $synchronizer,
								MessageMapper $messageMapper) {
		$this->imapClientFactory = $imapClientFactory;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailboxSync = $mailboxSync;
		$this->folderMapper = $folderMapper;
		$this->synchronizer = $synchronizer;
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param Account $account
	 *
	 * @return Folder[]
	 */
	public function getFolders(Account $account): array {
		$this->mailboxSync->sync($account);

		$folders = array_map(
			function (Mailbox $mb) {
				return $mb->toFolder();
			},
			$this->mailboxMapper->findAll($account)
		);
		$this->folderMapper->detectFolderSpecialUse($folders);
		return $folders;
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

	/**
	 * @param Account $account
	 * @param Request $syncRequest
	 *
	 * @return Response
	 */
	public function syncMessages(Account $account, Request $syncRequest): Response {
		$client = $this->imapClientFactory->getClient($account);

		return $this->synchronizer->sync($client, $syncRequest);
	}

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param Account $destinationAccount
	 * @param string $destFolderId
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
	 * @param Account $account
	 * @param string $sourceFolderId
	 * @param string $destFolderId
	 * @param int $messageId
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
