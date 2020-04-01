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

use OCA\Mail\Db\Alias;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;

interface IMailTransmission {

	/**
	 * Send a new message or reply to an existing one
	 *
	 * @param string $userId
	 * @param NewMessageData $message
	 * @param RepliedMessageData $reply
	 * @param Alias|null $alias
	 *
	 * @throws ServiceException
	 */
	public function sendMessage(NewMessageData $message,
								RepliedMessageData $reply = null,
								Alias $alias = null,
								int $draftUID = null);

	/**
	 * Save a message draft
	 *
	 * @param NewMessageData $message
	 * @param int $draftUID
	 *
	 * @return int
	 *
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, int $draftUID = null): int;
}
