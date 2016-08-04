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
use Horde_Mime_Part;
use OCP\Files\File;

class Message implements IMessage {

	use ConvertAddresses;

	/**
	 * @var string
	 */
	private $subject = '';

	/**
	 * @var string
	 */
	private $from = '';

	/**
	 *
	 * @var Horde_Mail_Rfc822_List
	 */
	private $to;

	/**
	 * @var Horde_Mail_Rfc822_List
	 */
	private $cc;

	/**
	 * @var Horde_Mail_Rfc822_List
	 */
	private $bcc;

	/**
	 * @var IMessage
	 */
	private $repliedMessage = null;

	/**
	 * @var array
	 */
	private $flags = [];

	/**
	 * @var string
	 */
	private $content = '';

	/**
	 * @var File[]
	 */
	private $attachments = [];

	/**
	 * @param string $list
	 * @return Horde_Mail_Rfc822_List
	 */
	public static function parseAddressList($list) {
		return new Horde_Mail_Rfc822_List($list);
	}

	public function __construct() {
		$this->to = new Horde_Mail_Rfc822_List();
		$this->cc = new Horde_Mail_Rfc822_List();
		$this->bcc = new Horde_Mail_Rfc822_List();
	}

	/**
	 * Get the ID
	 *
	 * @return int|null
	 */
	public function getMessageId() {
		return null;
	}

	/**
	 * Get all flags set on this message
	 * 
	 * @return array
	 */
	public function getFlags() {
		return $this->flags;
	}

	/**
	 * @param string[] $flags
	 */
	public function setFlags(array $flags) {
		$this->flags = $flags;
	}

	/**
	 * @return string
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * @param string $from
	 */
	public function setFrom($from) {
		$this->from = $from;
	}

	/**
	 * @return string
	 */
	public function getTo() {
		if ($this->to->count() > 0) {
			return $this->to->first()->writeAddress();
		}
		return null;
	}

	/**
	 * @param Horde_Mail_Rfc822_List $to
	 */
	public function setTo(Horde_Mail_Rfc822_List $to) {
		$this->to = $to;
	}

	/**
	 * @param bool $assoc
	 * @return string[]
	 */
	public function getToList($assoc = false) {
		if ($assoc) {
			return $this->hordeListToAssocArray($this->to);
		} else {
			return $this->hordeListToStringArray($this->to);
		}
	}

	/**
	 * @param bool $assoc
	 * @return Horde_Mail_Rfc822_List
	 */
	public function getCCList($assoc = false) {
		if ($assoc) {
			return $this->hordeListToAssocArray($this->cc);
		} else {
			return $this->hordeListToStringArray($this->cc);
		}
	}

	/**
	 * @param Horde_Mail_Rfc822_List $cc
	 */
	public function setCC(Horde_Mail_Rfc822_List $cc) {
		$this->cc = $cc;
	}

	/**
	 * @param bool $assoc
	 * @return Horde_Mail_Rfc822_List
	 */
	public function getBCCList($assoc = false) {
		if ($assoc) {
			return $this->hordeListToAssocArray($this->bcc);
		} else {
			return $this->hordeListToStringArray($this->bcc);
		}
	}

	/**
	 * @param Horde_Mail_Rfc822_List $bcc
	 */
	public function setBcc(Horde_Mail_Rfc822_List $bcc) {
		$this->bcc = $bcc;
	}

	/**
	 * @return IMessage
	 */
	public function getRepliedMessage() {
		return $this->repliedMessage;
	}

	/**
	 * @param IMessage $message
	 */
	public function setRepliedMessage(IMessage $message) {
		$this->repliedMessage = $message;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * @return File[]
	 */
	public function getAttachments() {
		return $this->attachments;
	}

	/**
	 * @param File $file
	 */
	public function addAttachmentFromFiles(File $file) {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition('attachment');
		$part->setName($file->getName());
		$part->setContents($file->getContent());
		$part->setType($file->getMimeType());
		$this->attachments[] = $part;
	}

}
