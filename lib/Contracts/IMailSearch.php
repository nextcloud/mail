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
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;

interface IMailSearch {

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
	public function findMessage(Account $account, string $mailboxName, int $uid): Message;

	/**
	 * @param Account $account
	 * @param string $mailboxName
	 * @param string|null $filter
	 * @param int|null $cursor
	 *
	 * @return Message[]
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function findMessages(Account $account, string $mailboxName, ?string $filter, ?int $cursor, ?int $limit): array;
}
