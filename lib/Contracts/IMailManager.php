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

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\Model\IMAPMessage;

interface IMailManager {

	/**
	 * @param Account $account
	 *
	 * @return Folder[]
	 *
	 * @throws ServiceException
	 */
	public function getFolders(Account $account): array;

	/**
	 * @param Account $account
	 * @param string $name
	 *
	 * @return Folder
	 *
	 * @throws ServiceException
	 */
	public function createFolder(Account $account, string $name): Folder;

	/**
	 * @param Account $account
	 * @param string $folderId
	 *
	 * @return FolderStats
	 *
	 * @throws ServiceException
	 */
	public function getFolderStats(Account $account, string $folderId): FolderStats;

	/**
	 * @param Account $account
	 * @param string $mb
	 * @param int $id
	 *
	 * @return string
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Account $account, string $mb, int $id): string;

	/**
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $id
	 * @param bool $loadBody
	 *
	 * @return IMAPMessage
	 *
	 * @throws ServiceException
	 */
	public function getMessage(Account $account, string $mailbox, int $id, bool $loadBody = false): IMAPMessage;

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 *
	 * @throws ServiceException
	 */
	public function moveMessage(Account $sourceAccount, string $sourceFolderId, int $messageId,
								Account $destinationAccount, string $destFolderId);

	/**
	 * @param Account $account
	 * @param string $mailboxId
	 * @param int $messageId
	 *
	 * @throws ServiceException
	 */
	public function deleteMessage(Account $account, string $mailboxId, int $messageId): void;

	/**
	 * Mark all messages of a folder as read
	 *
	 * @param Account $account
	 * @param string $folderId
	 *
	 * @throws ServiceException
	 */
	public function markFolderAsRead(Account $account, string $folderId): void;

	/**
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $uid
	 * @param string $flag
	 * @param bool $value
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function flagMessage(Account $account, string $mailbox, int $uid, string $flag, bool $value): void;
}
