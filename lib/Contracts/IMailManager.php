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
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderStats;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Quota;
use OCP\AppFramework\Db\DoesNotExistException;

interface IMailManager {

	/**
	 * @param string $uid
	 * @param int $id
	 *
	 * @return Mailbox
	 *
	 * @throws DoesNotExistException
	 */
	public function getMailbox(string $uid, int $id): Mailbox;

	/**
	 * @param Account $account
	 *
	 * @return Mailbox[]
	 *
	 * @throws ServiceException
	 */
	public function getMailboxes(Account $account): array;

	/**
	 * @param Account $account
	 * @param string $name
	 *
	 * @return Mailbox
	 *
	 * @throws ServiceException
	 */
	public function createMailbox(Account $account, string $name): Mailbox;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @return FolderStats
	 */
	public function getMailboxStats(Account $account, Mailbox $mailbox): FolderStats;

	/**
	 * @param Mailbox $mailbox
	 * @param $uid
	 *
	 * @return int|null
	 */
	public function getMessageIdForUid(Mailbox $mailbox, $uid): ?int;

	/**
	 * @param string $uid
	 * @param int $id
	 *
	 * @return Message
	 *
	 * @throws ClientException
	 */
	public function getMessage(string $uid, int $id): Message;

	/**
	 * @param Account $account
	 * @param string $mailbox
	 * @param int $uid
	 *
	 * @return string
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function getSource(Account $account, string $mailbox, int $uid): ?string;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @param bool $loadBody
	 *
	 * @return IMAPMessage
	 *
	 * @throws ServiceException
	 */
	public function getImapMessage(Account $account,
								   Mailbox $mailbox,
								   int $uid,
								   bool $loadBody = false): IMAPMessage;

	/**
	 * @param Account $account
	 * @param int $messageId database message ID
	 *
	 * @return Message[]
	 */
	public function getThread(Account $account, int $messageId): array;

	/**
	 * @param Account $sourceAccount
	 * @param string $sourceFolderId
	 * @param int $uid
	 * @param Account $destinationAccount
	 * @param string $destFolderId
	 *
	 * @throws ServiceException
	 */
	public function moveMessage(Account $sourceAccount,
								string $sourceFolderId,
								int $uid,
								Account $destinationAccount,
								string $destFolderId);

	/**
	 * @param Account $account
	 * @param string $mailboxId
	 * @param int $messageId
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function deleteMessage(Account $account, string $mailboxId, int $messageId): void;

	/**
	 * Mark all messages of a folder as read
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 */
	public function markFolderAsRead(Account $account, Mailbox $mailbox): void;

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

	/**
	 * @param Account $account
	 *
	 * @return Quota|null
	 */
	public function getQuota(Account $account): ?Quota;

	/**
	 * Rename a mailbox and get the new (cached) version
	 *
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param string $name
	 *
	 * @return Mailbox
	 *
	 * @throw ServiceException
	 */
	public function renameMailbox(Account $account, Mailbox $mailbox, string $name): Mailbox;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function deleteMailbox(Account $account, Mailbox $mailbox): void;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param bool $subscribed
	 *
	 * @return Mailbox
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function updateSubscription(Account $account,
									   Mailbox $mailbox,
									   bool $subscribed): Mailbox;

	/**
	 * @param Mailbox $mailbox
	 * @param bool $syncInBackground
	 *
	 * @return Mailbox
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function enableMailboxBackgroundSync(Mailbox $mailbox,
												bool $syncInBackground): Mailbox;
}
