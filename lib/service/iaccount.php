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

use OCA\Mail\Model\IMessage;

interface IAccount {

	/**
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * @return array
	 * TODO: function name is :hankey:
	 */
	public function getListArray();

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
	 * @param IMessage $message
	 * @param int|null $draftUID
	 */
	public function sendMessage(IMessage $message, $draftUID);

	/**
	 * @param IMessage $message
	 * @param int|null $previousUID
	 * @return int
	 */
	public function saveDraft(IMessage $message, $previousUID);

	/**
	 * @param string $folderId
	 * @param int $messageId
	 */
	public function deleteMessage($folderId, $messageId);

	/**
	 * @param string[] $query
	 * @return array
	 */
	public function getChangedMailboxes($query);

	/**
	 * @return IMailBox
	 */
	public function getInbox();

	/**
	 * @return int
	 */
	public function getId();
}
