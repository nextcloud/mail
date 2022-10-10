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
