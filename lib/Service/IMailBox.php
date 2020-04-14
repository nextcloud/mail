<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
use OCA\Mail\Attachment;
use OCA\Mail\Model\IMessage;

interface IMailBox {

	/**
	 * @return string
	 */
	public function getFolderId(): string;

	/**
	 * @return string|null
	 */
	public function getSpecialRole();

	/**
	 * @param int $id
	 * @return IMessage
	 */
	public function getMessage(int $id, bool $loadHtmlMessageBody = false);

	/**
	 * @param int $messageId
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getAttachment(int $messageId, string $attachmentId): Attachment;

	/**
	 * @param int $flags
	 * @return array
	 */
	public function getStatus(int $flags = Horde_Imap_Client::STATUS_ALL): array;
}
