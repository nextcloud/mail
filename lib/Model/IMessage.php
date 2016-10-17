<?php

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
namespace OCA\Mail\Model;

use Horde_Mail_Rfc822_List;
use OCP\Files\File;

interface IMessage {

	/**
	 * Get the ID if available
	 *
	 * @return int|null
	 */
	public function getMessageId();

	/**
	 * Get all flags set on this message
	 * 
	 * @return array
	 */
	public function getFlags();

	/**
	 * @param array $flags
	 */
	public function setFlags(array $flags);

	/**
	 * @return string
	 */
	public function getFrom();

	/**
	 * @param string $from
	 */
	public function setFrom($from);

	/**
	 * @return string
	 */
	public function getTo();

	/**
	 * @param Horde_Mail_Rfc822_List $to
	 */
	public function setTo(Horde_Mail_Rfc822_List $to);

	/**
	 * @param bool $assoc
	 * @return string[]
	 */
	public function getToList($assoc = false);

	/**
	 * @param bool $assoc
	 * @return string[]
	 */
	public function getCCList($assoc = false);

	/**
	 * @param Horde_Mail_Rfc822_List $cc
	 */
	public function setCC(Horde_Mail_Rfc822_List $cc);

	/**
	 * @param bool $assoc
	 * @return string[]
	 */
	public function getBCCList($assoc = false);

	/**
	 * @param Horde_Mail_Rfc822_List $bcc
	 */
	public function setBcc(Horde_Mail_Rfc822_List $bcc);

	/**
	 * @return IMessage
	 */
	public function getRepliedMessage();

	/**
	 * @param IMessage $message
	 */
	public function setRepliedMessage(IMessage $message);

	/**
	 * @return string
	 */
	public function getSubject();

	/**
	 * @param string $subject
	 */
	public function setSubject($subject);

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @param string $content
	 */
	public function setContent($content);

	/**
	 * @return \Horde_Mime_Part[]
	 */
	public function getAttachments();

	/**
	 * @param File $fileName
	 */
	public function addAttachmentFromFiles(File $fileName);
}
