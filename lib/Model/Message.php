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

namespace OCA\Mail\Model;

use Horde_Mime_Part;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;

class Message implements IMessage {
	use ConvertAddresses;

	/** @var string */
	private $subject = '';

	/** @var AddressList */
	private $from;

	/** @var AddressList */
	private $to;

	/** @var AddressList */
	private $cc;

	/** @var AddressList */
	private $bcc;

	/** @var string|null */
	private $inReplyTo = null;

	/** @var array */
	private $flags = [];

	/** @var string */
	private $content = '';

	/** @var File[] */
	private $cloudAttachments = [];

	/** @var int[] */
	private $localAttachments = [];

	public function __construct() {
		$this->from = new AddressList();
		$this->to = new AddressList();
		$this->cc = new AddressList();
		$this->bcc = new AddressList();
	}

	/**
	 * Get the ID
	 *
	 * @return string|null
	 */
	public function getMessageId() {
		return null;
	}

	/**
	 * Get all flags set on this message
	 *
	 * @return array
	 */
	public function getFlags(): array {
		return $this->flags;
	}

	/**
	 * @param string[] $flags
	 *
	 * @return void
	 */
	public function setFlags(array $flags) {
		$this->flags = $flags;
	}

	/**
	 * @return AddressList
	 */
	public function getFrom(): AddressList {
		return $this->from;
	}

	/**
	 * @param AddressList $from
	 *
	 * @return void
	 */
	public function setFrom(AddressList $from) {
		$this->from = $from;
	}

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList {
		return $this->to;
	}

	/**
	 * @param AddressList $to
	 *
	 * @return void
	 */
	public function setTo(AddressList $to) {
		$this->to = $to;
	}

	/**
	 * @return AddressList
	 */
	public function getCC(): AddressList {
		return $this->cc;
	}

	/**
	 * @param AddressList $cc
	 *
	 * @return void
	 */
	public function setCC(AddressList $cc) {
		$this->cc = $cc;
	}

	/**
	 * @return AddressList
	 */
	public function getBCC(): AddressList {
		return $this->bcc;
	}

	/**
	 * @param AddressList $bcc
	 *
	 * @return void
	 */
	public function setBcc(AddressList $bcc) {
		$this->bcc = $bcc;
	}

	/**
	 * @return string|null
	 */
	public function getInReplyTo() {
		return $this->inReplyTo;
	}

	/**
	 * @param IMessage $message
	 *
	 * @return void
	 */
	public function setInReplyTo(string $id) {
		$this->inReplyTo = $id;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 *
	 * @return void
	 */
	public function setSubject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * @param string $content
	 *
	 * @return void
	 */
	public function setContent(string $content) {
		$this->content = $content;
	}

	/**
	 * @return File[]
	 */
	public function getCloudAttachments(): array {
		return $this->cloudAttachments;
	}

	/**
	 * @return int[]
	 */
	public function getLocalAttachments(): array {
		return $this->localAttachments;
	}

	/**
	 * @param File $file
	 *
	 * @return void
	 */
	public function addAttachmentFromFiles(File $file) {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition('attachment');
		$part->setName($file->getName());
		$part->setContents($file->getContent());
		$part->setType($file->getMimeType());
		$this->cloudAttachments[] = $part;
	}

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 *
	 * @return void
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file) {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition('attachment');
		$part->setName($attachment->getFileName());
		$part->setContents($file->getContent());
		$part->setType($file->getMimeType());
		$this->localAttachments[] = $part;
	}
}
