<?php

namespace OCA\Mail\Model;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use Horde_Mail_Rfc822_List;
use Horde_Mime_Part;
use OCP\Files\File;

class Message implements IMessage {

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
	 * @var string[]
	 */
	private $to = [];

	/**
	 * @var string[]
	 */
	private $cc = [];

	/**
	 * @var string[]
	 */
	private $bcc = [];

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
	 * @return string[]
	 */
	public static function parseAddressList($list) {
		$hordeList = new Horde_Mail_Rfc822_List($list);
		$addresses = [];
		foreach ($hordeList as $address) {
			$addresses[] = $address->bare_address;
		}
		return $addresses;
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
	 * @param array $flags
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
		if (count($this->to) > 0) {
			return $this->to[0];
		}
		return null;
	}

	/**
	 * @param string[] $to
	 */
	public function setTo(array $to) {
		$this->to = $to;
	}

	/**
	 * @return string[]
	 */
	public function getToList() {
		return $this->to;
	}

	/**
	 * @return string[]
	 */
	public function getCCList() {
		return $this->cc;
	}

	/**
	 * @param string[] $cc
	 */
	public function setCC(array $cc) {
		$this->cc = $cc;
	}

	/**
	 * @return string[]
	 */
	public function getBCCList() {
		return $this->bcc;
	}

	/**
	 * @param array $bcc
	 */
	public function setBcc(array $bcc) {
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
	 * @return Horde_Mime_Part[]
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
