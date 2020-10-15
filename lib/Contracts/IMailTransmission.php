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
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\SentMailboxNotSetException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;

interface IMailTransmission {

	/**
	 * Send a new message or reply to an existing one
	 *
	 * @param NewMessageData $messageData
	 * @param RepliedMessageData|null $replyData
	 * @param Alias|null $alias
	 * @param Message|null $draft
	 *
	 * @throws SentMailboxNotSetException
	 * @throws ServiceException
	 */
	public function sendMessage(NewMessageData $messageData,
								RepliedMessageData $replyData = null,
								Alias $alias = null,
								Message $draft = null): void;

	/**
	 * Save a message draft
	 *
	 * @param NewMessageData $message
	 * @param Message|null $previousDraft
	 *
	 * @return array
	 *
	 * @throws ClientException if no drafts mailbox is configured
	 * @throws ServiceException
	 */
	public function saveDraft(NewMessageData $message, Message $previousDraft = null): array;
}
