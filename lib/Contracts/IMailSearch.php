<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Search\Result;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;

interface IMailSearch {
	public const ORDER_NEWEST_FIRST = 'newest-first';
	public const ORDER_OLDEST_FIRST = 'oldest-first';

	/**
	 * @param Account $account
	 * @param string $mailboxName
	 * @param int $uid
	 *
	 * @return Message
	 * @throws DoesNotExistException
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessage(Account $account,
								Mailbox $mailbox,
								Message $message): Message;

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param string|null $filter
	 * @param int|null $cursor
	 * @param int|null $limit
	 * @param string $sortOrder
	 * @psalm-param IMailSearch::ORDER_* $sortOrder
	 *
	 * @return Result
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessages(Account $account,
								 Mailbox $mailbox,
								 ?string $filter,
								 ?int $cursor,
								 ?int $limit,
								 string $sortOrder): Result;

	/**
	 * @param IUser $user
	 * @param string|null $filter
	 * @param int|null $cursor
	 *
	 * @return Message[]
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessagesGlobally(IUser $user, ?string $filter, ?int $cursor, ?int $limit): array;
}
