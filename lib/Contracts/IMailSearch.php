<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;

interface IMailSearch {
	public const ORDER_NEWEST_FIRST = 'DESC';
	public const ORDER_OLDEST_FIRST = 'ASC';
	/**
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
	 * @param string $sortOrder
	 * @param string|null $filter
	 * @param int|null $cursor
	 * @param int|null $limit
	 *
	 * @return Message[]
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessages(Account $account,
		Mailbox $mailbox,
		string $sortOrder,
		?string $filter,
		?int $cursor,
		?int $limit): array;

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
