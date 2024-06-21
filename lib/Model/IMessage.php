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
	 *
	 * @return array
	 */
	public function getFlags(): array;

	/**
	 * @param string[] $flags
	 */
	public function setFlags(array $flags);

	/**
	 * @return AddressList
	 */
	public function getFrom(): AddressList;

	/**
	 * @param AddressList $from
	 */
	public function setFrom(AddressList $from);

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList;

	/**
	 * @param AddressList $to
	 */
	public function setTo(AddressList $to);

	/**
	 * @return AddressList
	 */
	public function getReplyTo(): AddressList;

	/**
	 * @param AddressList $replyTo
	 */
	public function setReplyTo(AddressList $replyTo);

	/**
	 * @return AddressList
	 */
	public function getCC(): AddressList;

	/**
	 * @param AddressList $cc
	 */
	public function setCC(AddressList $cc);

	/**
	 * @return AddressList
	 */
	public function getBCC(): AddressList;

	/**
	 * @param AddressList $bcc
	 */
	public function setBcc(AddressList $bcc);

	/**
	 * @return string|null
	 */
	public function getInReplyTo();

	/**
	 * @param string $id
	 */
	public function setInReplyTo(string $id);

	/**
	 * @return string
	 */
	public function getSubject(): string;

	/**
	 * @param string $subject
	 */
	public function setSubject(string $subject);

	/**
	 * @return string
	 */
	public function getContent(): string;

	/**
	 * @param string $content
	 */
	public function setContent(string $content);

	/**
	 * @return Horde_Mime_Part[]
	 */
	public function getAttachments(): array;

	/**
	 * @param string $name
	 * @param string $content attached with mime type 'application/octet-stream'
	 */
	public function addRawAttachment(string $name, string $content): void;

	/**
	 * @param string $name
	 * @param string $content attached with mime type 'message/rfc822'
	 *
	 * @return void
	 */
	public function addEmbeddedMessageAttachment(string $name, string $content): void;

	/**
	 * @param File $file
	 */
	public function addAttachmentFromFiles(File $file);

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file);
}
