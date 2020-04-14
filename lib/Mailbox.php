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
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\IMailBox;

class Mailbox implements IMailBox {

	/**
	 * @var Horde_Imap_Client_Socket
	 */
	protected $conn;

	/**
	 * @var array
	 */
	private $attributes;

	/**
	 * @var string
	 */
	private $specialRole;

	/**
	 * @var string
	 */
	private $displayName;

	/**
	 * @var string
	 */
	private $delimiter;

	/**
	 * @var Horde_Imap_Client_Mailbox
	 */
	protected $mailBox;

	/**
	 * @param Horde_Imap_Client_Socket $conn
	 * @param Horde_Imap_Client_Mailbox $mailBox
	 * @param array $attributes
	 * @param string $delimiter
	 */
	public function __construct($conn, $mailBox, $attributes, $delimiter = '/') {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
		$this->attributes = $attributes;
		$this->delimiter = $delimiter;
		$this->getSpecialRoleFromAttributes();
		if ($this->specialRole === null) {
			$this->guessSpecialRole();
		}
		$this->makeDisplayName();
	}

	/**
	 * @return array
	 */
	public function attributes() {
		return $this->attributes;
	}

	/**
	 * @param int $messageId
	 * @param bool $loadHtmlMessageBody
	 *
	 * @return IMAPMessage
	 */
	public function getMessage(int $messageId, bool $loadHtmlMessageBody = false) {
		return new IMAPMessage($this->conn, $this->mailBox, $messageId, null, $loadHtmlMessageBody);
	}

	/**
	 * @return array
	 */
	public function getStatus(int $flags = Horde_Imap_Client::STATUS_ALL): array {
		return $this->conn->status($this->mailBox, $flags);
	}

	/**
	 * @return int
	 */
	public function getTotalMessages() {
		$status = $this->getStatus(\Horde_Imap_Client::STATUS_MESSAGES);
		return (int) $status['messages'];
	}

	protected function makeDisplayName(): void {
		$parts = explode($this->delimiter, $this->mailBox->utf8, 2);

		if (count($parts) > 1) {
			$displayName = $parts[1];
		} elseif (strtolower($this->mailBox->utf8) === 'inbox') {
			$displayName = 'Inbox';
		} else {
			$displayName = $this->mailBox->utf8;
		}

		$this->displayName = $displayName;
	}

	public function getFolderId(): string {
		return $this->mailBox->utf8;
	}

	/**
	 * @return null|string
	 */
	public function getParent(): ?string {
		$folderId = $this->getFolderId();
		$parts = explode($this->delimiter, $folderId, 2);

		if (count($parts) > 1) {
			return $parts[0];
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getSpecialRole() {
		return $this->specialRole;
	}

	/**
	 * @return string
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * @param string $displayName
	 *
	 * @return void
	 */
	public function setDisplayName($displayName): void {
		$this->displayName = $displayName;
	}

	/**
	 * @param integer $accountId
	 * @return array
	 */
	public function serialize($accountId, $status = null) {
		$displayName = $this->getDisplayName();
		try {
			if (is_null($status)) {
				$status = $this->getStatus();
			}
			$total = $status['messages'];
			$specialRole = $this->getSpecialRole();
			$unseen = ($specialRole === 'trash') ? 0 : $status['unseen'];
			$isEmpty = ($total === 0);
			$noSelect = in_array('\\noselect', $this->attributes);
			$parentId = $this->getParent();
			$parentId = ($parentId !== null) ? base64_encode($parentId) : null;
			return [
				'id' => base64_encode($this->getFolderId()),
				'parent' => $parentId,
				'name' => $displayName,
				'specialRole' => $specialRole,
				'unseen' => $unseen,
				'total' => $total,
				'isEmpty' => $isEmpty,
				'accountId' => $accountId,
				'noSelect' => $noSelect,
				'uidvalidity' => $status['uidvalidity'],
				'uidnext' => $status['uidnext'],
				'delimiter' => $this->delimiter
			];
		} catch (Horde_Imap_Client_Exception $e) {
			return [
				'id' => base64_encode($this->getFolderId()),
				'parent' => null,
				'name' => $displayName,
				'specialRole' => null,
				'unseen' => 0,
				'total' => 0,
				'error' => $e->getMessage(),
				'isEmpty' => true,
				'accountId' => $accountId,
				'noSelect' => true
			];
		}
	}

	/**
	 * Get the special use role of the mailbox
	 *
	 * This method reads the attributes sent by the server
	 *
	 * @return void
	 */
	protected function getSpecialRoleFromAttributes(): void {
		/*
		 * @todo: support multiple attributes on same folder
		 * "any given server or  message store may support
		 *  any combination of the attributes"
		 *  https://tools.ietf.org/html/rfc6154
		 */
		$result = null;
		if (is_array($this->attributes)) {
			/* Convert attributes to lowercase, because gmail
			 * returns them as lowercase (eg. \trash and not \Trash)
			 */
			$specialUseAttributes = [
				strtolower(Horde_Imap_Client::SPECIALUSE_ALL),
				strtolower(Horde_Imap_Client::SPECIALUSE_ARCHIVE),
				strtolower(Horde_Imap_Client::SPECIALUSE_DRAFTS),
				strtolower(Horde_Imap_Client::SPECIALUSE_FLAGGED),
				strtolower(Horde_Imap_Client::SPECIALUSE_JUNK),
				strtolower(Horde_Imap_Client::SPECIALUSE_SENT),
				strtolower(Horde_Imap_Client::SPECIALUSE_TRASH)
			];

			$attributes = array_map(function ($n) {
				return strtolower($n);
			}, $this->attributes);

			foreach ($specialUseAttributes as $attr) {
				if (in_array($attr, $attributes)) {
					$result = ltrim($attr, '\\');
					break;
				}
			}
		}

		$this->specialRole = $result;
	}

	/**
	 * Assign a special role to this mailbox based on its name
	 *
	 * @return void
	 */
	protected function guessSpecialRole(): void {
		$specialFoldersDict = [
			'inbox' => ['inbox'],
			'sent' => ['sent', 'sent items', 'sent messages', 'sent-mail', 'sentmail'],
			'drafts' => ['draft', 'drafts'],
			'archive' => ['archive', 'archives'],
			'trash' => ['deleted messages', 'trash'],
			'junk' => ['junk', 'spam', 'bulk mail'],
		];

		$lowercaseExplode = explode($this->delimiter, $this->getFolderId(), 2);
		$lowercaseId = strtolower(array_pop($lowercaseExplode));
		$result = null;
		foreach ($specialFoldersDict as $specialRole => $specialNames) {
			if (in_array($lowercaseId, $specialNames)) {
				$result = $specialRole;
				break;
			}
		}

		$this->specialRole = $result;
	}

	/**
	 * @param int $messageId
	 * @param string $attachmentId
	 * @return Attachment
	 */
	public function getAttachment(int $messageId, string $attachmentId): Attachment {
		return new Attachment($this->conn, $this->mailBox, $messageId, $attachmentId);
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
