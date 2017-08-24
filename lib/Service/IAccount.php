<?php

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

use JsonSerializable;

interface IAccount extends JsonSerializable {

	/**
	 * @param $folderId
	 * @return IMailbox
	 */
	public function getMailbox($folderId);

	/**
	 * @return string
	 */
	public function getEmail();

	/**
	 * @param string $folderId
	 * @param int $messageId
	 */
	public function deleteMessage($folderId, $messageId);

	/**
	 * @param string $sourceFolderId
	 * @param int $messageId
	 * @param string $destFolderId
	 */
	public function moveMessage($sourceFolderId, $messageId, $destFolderId);

	/**
	 * @return IMailBox
	 */
	public function getInbox();

	/**
	 * @return int
	 */
	public function getId();
}
