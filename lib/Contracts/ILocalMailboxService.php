<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;

interface ILocalMailboxService {
	/**
	 * @param string $userId
	 * @return mixed
	 */
	public function getMessages(string $userId): array;

	/**
	 * @param int $id
	 *
	 * @return LocalMessage
	 *
	 * @throws ServiceException
	 */
	public function getMessage(int $id, string $userId): LocalMessage;

	/**
	 * @param Account $account
	 * @param LocalMessage $message
	 * @param Recipient[] $to
	 * @param Recipient[] $cc
	 * @param Recipient[] $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function saveMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage;

	/**
	 * @param LocalMessage $message
	 * @param Recipient[] $to
	 * @param Recipient[] $cc
	 * @param Recipient[] $bcc
	 * @param array $attachments
	 * @return LocalMessage
	 */
	public function updateMessage(Account $account, LocalMessage $message, array $to, array $cc, array $bcc, array $attachments = []): LocalMessage;

	/**
	 * @param LocalMessage $message
	 * @param string $userId
	 */
	public function deleteMessage(string $userId, LocalMessage $message): void;

	/**
	 * @param LocalMessage $message
	 * @param Account $account
	 * @throws ClientException
	 * @throws ServiceException
	 * @return void
	 */
	public function sendMessage(LocalMessage $message, Account $account): void;
}
