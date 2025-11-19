<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Model;

use Horde_Mime_Part;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;

interface IMessage {
	/**
	 * Get the ID if available
	 *
	 * @return string|null
	 */
	public function getMessageId();

	/**
	 * Get all flags set on this message
	 */
	public function getFlags(): array;

	/**
	 * @param string[] $flags
	 */
	public function setFlags(array $flags);

	public function getFrom(): AddressList;

	public function setFrom(AddressList $from);

	public function getTo(): AddressList;

	public function setTo(AddressList $to);

	public function getReplyTo(): AddressList;

	public function setReplyTo(AddressList $replyTo);

	public function getCC(): AddressList;

	public function setCC(AddressList $cc);

	public function getBCC(): AddressList;

	public function setBcc(AddressList $bcc);

	/**
	 * @return string|null
	 */
	public function getInReplyTo();

	public function setInReplyTo(string $id);

	public function getSubject(): string;

	public function setSubject(string $subject);

	public function getContent(): string;

	public function setContent(string $content);

	/**
	 * @return Horde_Mime_Part[]
	 */
	public function getAttachments(): array;

	/**
	 * @param string $content attached with mime type 'application/octet-stream'
	 */
	public function addRawAttachment(string $name, string $content): void;

	/**
	 * @param string $content attached with mime type 'message/rfc822'
	 *
	 */
	public function addEmbeddedMessageAttachment(string $name, string $content): void;

	public function addAttachmentFromFiles(File $file);

	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file);
}
