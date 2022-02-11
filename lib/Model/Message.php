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

use finfo;
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

	/** @var string[] */
	private $flags = [];

	/** @var string */
	private $content = '';

	/** @var Horde_Mime_Part[] */
	private $attachments = [];

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
	 * @return string[]
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
	 * @return Horde_Mime_Part[]
	 */
	public function getAttachments(): array {
		return $this->attachments;
	}

	/**
	 * Adds a file that's coming from another email's attachment (typical
	 * use case is forwarding a message)
	 */
	public function addRawAttachment(string $name, string $content): void {
		$mime = 'application/octet-stream';
		if (extension_loaded('fileinfo')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$detectedMime = $finfo->buffer($content);
			if ($detectedMime !== false) {
				$mime = $detectedMime;
			}
		}

		$this->createAttachmentDetails($name, $content, $mime);
	}

	/**
	 * @param string $name
	 * @param string $content
	 *
	 * @return void
	 */
	public function addEmbeddedMessageAttachment(string $name, string $content): void {
		$this->createAttachmentDetails($name, $content, 'message/rfc822');
	}

	/**
	 * @param File $file
	 *
	 * @return void
	 */
	public function addAttachmentFromFiles(File $file): void {
		$this->createAttachmentDetails($file->getName(), $file->getContent(), $file->getMimeType());
	}

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 *
	 * @return void
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file): void {
		$this->createAttachmentDetails($attachment->getFileName(), $file->getContent(), $attachment->getMimeType());
	}

	/**
	 * @param string $name
	 * @param string $content
	 * @param string $mime
	 * @return void
	 */
	private function createAttachmentDetails(string $name, string $content, string $mime): void {
		$part = new Horde_Mime_Part();
		$part->setCharset('us-ascii');
		$part->setDisposition('attachment');
		$part->setName($name);
		$part->setContents($content);
		$part->setType($mime);
		$this->attachments[] = $part;
	}
}
