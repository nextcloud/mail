<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Model;

use finfo;
use Horde_Mime_Part;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;

final class Message implements IMessage {
	use ConvertAddresses;

	private string $subject = '';

	private \OCA\Mail\AddressList $from;

	private \OCA\Mail\AddressList $to;

	private \OCA\Mail\AddressList $replyTo;

	private \OCA\Mail\AddressList $cc;

	private \OCA\Mail\AddressList $bcc;

	private ?string $inReplyTo = null;

	/** @var string[] */
	private array $flags = [];

	private string $content = '';

	/** @var Horde_Mime_Part[] */
	private array $attachments = [];

	public function __construct() {
		$this->from = new AddressList();
		$this->to = new AddressList();
		$this->replyTo = new AddressList();
		$this->cc = new AddressList();
		$this->bcc = new AddressList();
	}

	/**
	 * Get the ID
	 *
	 * @return string|null
	 */
	#[\Override]
	public function getMessageId() {
		return null;
	}

	/**
	 * Get all flags set on this message
	 *
	 * @return string[]
	 */
	#[\Override]
	public function getFlags(): array {
		return $this->flags;
	}

	/**
	 * @param string[] $flags
	 */
	#[\Override]
	public function setFlags(array $flags): void {
		$this->flags = $flags;
	}

	#[\Override]
	public function getFrom(): AddressList {
		return $this->from;
	}

	#[\Override]
	public function setFrom(AddressList $from): void {
		$this->from = $from;
	}

	#[\Override]
	public function getTo(): AddressList {
		return $this->to;
	}

	#[\Override]
	public function setTo(AddressList $to): void {
		$this->to = $to;
	}

	#[\Override]
	public function getReplyTo(): AddressList {
		return $this->replyTo;
	}

	#[\Override]
	public function setReplyTo(AddressList $replyTo): void {
		$this->replyTo = $replyTo;
	}

	#[\Override]
	public function getCC(): AddressList {
		return $this->cc;
	}

	#[\Override]
	public function setCC(AddressList $cc): void {
		$this->cc = $cc;
	}

	#[\Override]
	public function getBCC(): AddressList {
		return $this->bcc;
	}

	#[\Override]
	public function setBcc(AddressList $bcc): void {
		$this->bcc = $bcc;
	}

	#[\Override]
	public function getInReplyTo(): ?string {
		return $this->inReplyTo;
	}

	#[\Override]
	public function setInReplyTo(string $id): void {
		$this->inReplyTo = $id;
	}

	#[\Override]
	public function getSubject(): string {
		return $this->subject;
	}

	#[\Override]
	public function setSubject(string $subject): void {
		$this->subject = $subject;
	}

	#[\Override]
	public function getContent(): string {
		return $this->content;
	}

	#[\Override]
	public function setContent(string $content): void {
		$this->content = $content;
	}

	/**
	 * @return Horde_Mime_Part[]
	 */
	#[\Override]
	public function getAttachments(): array {
		return $this->attachments;
	}

	/**
	 * Adds a file that's coming from another email's attachment (typical
	 * use case is forwarding a message)
	 */
	#[\Override]
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

	#[\Override]
	public function addEmbeddedMessageAttachment(string $name, string $content): void {
		$this->createAttachmentDetails($name, $content, 'message/rfc822');
	}

	#[\Override]
	public function addAttachmentFromFiles(File $file): void {
		$this->createAttachmentDetails($file->getName(), $file->getContent(), $file->getMimeType());
	}

	#[\Override]
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file): void {
		$this->createAttachmentDetails($attachment->getFileName(), $file->getContent(), $attachment->getMimeType());
	}

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
