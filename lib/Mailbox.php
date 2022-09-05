<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clement Wong <mail@clement.hk>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author matiasdelellis <mati86dl@gmail.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas I <thomas@oatr.be>
 * @author Thomas Mueller <thomas.mueller@tmit.eu>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Maximilian Zellhofer <max.zellhofer@gmail.com>
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

namespace OCA\Mail;

use Horde_Imap_Client;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Model\IMAPMessage;

class Mailbox {
	/**
	 * @var Horde_Imap_Client_Socket
	 */
	protected $conn;

	/**
	 * @var Horde_Imap_Client_Mailbox
	 */
	protected $mailBox;

	/**
	 * @param Horde_Imap_Client_Socket $conn
	 * @param Horde_Imap_Client_Mailbox $mailBox
	 */
	public function __construct($conn, $mailBox) {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
	}

	/**
	 * @param int $uid
	 * @param bool $loadHtmlMessageBody
	 *
	 * @return IMAPMessage
	 */
	public function getMessage(int $uid, bool $loadHtmlMessageBody = false) {
		return new IMAPMessage($this->conn, $this->mailBox, $uid, null, $loadHtmlMessageBody);
	}

	/**
	 * @param int $messageUid
	 * @param string $attachmentId
	 *
	 * @return Attachment
	 */
	public function getAttachment(int $messageUid, string $attachmentId): Attachment {
		return new Attachment($this->conn, $this->mailBox, $messageUid, $attachmentId);
	}

	/**
	 * @param string $rawBody
	 * @param array $flags
	 * @return array<int> UIDs
	 *
	 * @deprecated only used for testing
	 */
	public function saveMessage($rawBody, $flags = []) {
		$uids = $this->conn->append($this->mailBox, [
			[
				'data' => $rawBody,
				'flags' => $flags
			]
		])->ids;

		return reset($uids);
	}
}
