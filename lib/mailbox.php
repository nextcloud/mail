<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clement Wong <mail@clement.hk>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author matiasdelellis <mati86dl@gmail.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas I <thomas@oatr.be>
 * @author Thomas Mueller <thomas.mueller@tmit.eu>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
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
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Search_Query;
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
	public function __construct($conn, $mailBox, $attributes, $delimiter='/') {
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

	private function getSearchIds($from, $count, $filter) {
		if ($filter instanceof Horde_Imap_Client_Search_Query) {
			$query = $filter;
		} else {
			$query = new Horde_Imap_Client_Search_Query();
			if ($filter) {
				$query->text($filter, false);
			}
		}
		if ($this->getSpecialRole() !== 'trash') {
			$query->flag(Horde_Imap_Client::FLAG_DELETED, false);
		}
		$result = $this->conn->search($this->mailBox, $query, ['sort' => [Horde_Imap_Client::SORT_DATE]]);
		$ids = array_reverse($result['match']->ids);
		if ($from >= 0 && $count >= 0) {
			$ids = array_slice($ids, $from, $count);
		}
		return new \Horde_Imap_Client_Ids($ids, false);
	}

	private function getFetchIds($from, $count) {
		$q = new Horde_Imap_Client_Fetch_Query();
		$q->uid();
		$q->imapDate();
		// FIXME: $q->headers('DATE', ['DATE']); could be a better option than the INTERNALDATE

		$result = $this->conn->fetch($this->mailBox, $q);
		$uidMap = [];
		foreach ($result as $r) {
			$uidMap[$r->getUid()] = $r->getImapDate()->getTimeStamp();
		}
		// sort by time
		uasort($uidMap, function($a, $b) {
			return $a < $b;
		});
		if ($from >= 0 && $count >= 0) {
			$uidMap = array_slice($uidMap, $from, $count, true);
		}
		return new \Horde_Imap_Client_Ids(array_keys($uidMap), false);
	}

	public function getMessages($from = 0, $count = 2, $filter = '') {
		if (is_null($filter) || $filter === '') {
			$ids = $this->getFetchIds($from, $count);
		} else {
			$ids = $this->getSearchIds($from, $count, $filter);
		}

		$headers = [];

		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->flags();
		$fetch_query->size();
		$fetch_query->uid();
		$fetch_query->imapDate();
		$fetch_query->structure();

		$headers = array_merge($headers, [
			'importance',
			'list-post',
			'x-priority'
		]);
		$headers[] = 'content-type';

		$fetch_query->headers('imp', $headers, [
			'cache' => true,
			'peek'  => true
		]);

		$options = ['ids' => $ids];
		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$headers = $this->conn->fetch($this->mailBox, $fetch_query, $options);

		ob_start(); // fix for Horde warnings
		$messages = [];
		foreach ($headers->ids() as $message_id) {
			$header = $headers[$message_id];
			$message = new IMAPMessage($this->conn, $this->mailBox, $message_id, $header);
			$messages[] = $message->getListArray();
		}
		ob_get_clean();

		// sort by time
		usort($messages, function($a, $b) {
			return $a['dateInt'] < $b['dateInt'];
		});

		return $messages;
	}

	/**
	 * @return array
	 */
	public function attributes() {
		return $this->attributes;
	}

	/**
	 * @param string $messageId
	 * @param bool $loadHtmlMessageBody
	 * @return IMAPMessage
	 */
	public function getMessage($messageId, $loadHtmlMessageBody = false) {
		return new IMAPMessage($this->conn, $this->mailBox, $messageId, null, $loadHtmlMessageBody);
	}

	/**
	 * @param int $flags
	 * @return array
	 */
	public function getStatus($flags = \Horde_Imap_Client::STATUS_ALL) {
		return $this->conn->status($this->mailBox, $flags);
	}

	/**
	 * @return int
	 */
	public function getTotalMessages() {
		$status = $this->getStatus(\Horde_Imap_Client::STATUS_MESSAGES);
		return (int) $status['messages'];
	}

	protected function makeDisplayName() {
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

	public function getFolderId() {
		return $this->mailBox->utf8;
	}

	/**
	 * @return string
	 */
	public function getParent() {
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
	 */
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
	}

	/**
	 * @param integer $accountId
	 * @return array
	 */
	public function getListArray($accountId, $status = null) {
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
	 */
	protected function getSpecialRoleFromAttributes() {
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

			$attributes = array_map(function($n) {
				return strtolower($n);
			}, $this->attributes);

			foreach ($specialUseAttributes as $attr)  {
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
	 */
	protected function guessSpecialRole() {

		$specialFoldersDict = [
			'inbox'   => ['inbox'],
			'sent'    => ['sent', 'sent items', 'sent messages', 'sent-mail', 'sentmail'],
			'drafts'  => ['draft', 'drafts'],
			'archive' => ['archive', 'archives'],
			'trash'   => ['deleted messages', 'trash'],
			'junk'    => ['junk', 'spam', 'bulk mail'],
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
	public function getAttachment($messageId, $attachmentId) {
		return new Attachment($this->conn, $this->mailBox, $messageId, $attachmentId);
	}

	/**
	 * @param string $rawBody
	 * @param array $flags
	 */
	public function saveMessage($rawBody, $flags = []) {

		$this->conn->append($this->mailBox, [
			[
				'data' => $rawBody,
				'flags' => $flags
			]
		]);
	}

	/**
	 * Save draft
	 *
	 * @param string $rawBody
	 * @return int UID of the saved draft
	 */
	public function saveDraft($rawBody) {

		$uids = $this->conn->append($this->mailBox, [
			[
				'data' => $rawBody,
				'flags' => [
					Horde_Imap_Client::FLAG_DRAFT,
					Horde_Imap_Client::FLAG_SEEN
				]
			]
		]);
		return $uids->current();
	}

	/**
	 * @param int $uid
	 * @param string $flag
	 * @param boolean $add
	 */
	public function setMessageFlag($uid, $flag, $add) {
		$options = [
			'ids' => new Horde_Imap_Client_Ids($uid)
		];
		if ($add) {
			$options['add'] = [$flag];
		} else {
			$options['remove'] = [$flag];
		}
		$this->conn->store($this->mailBox, $options);
	}

	/**
	 * @param $fromUid
	 * @param $toUid
	 * @return array
	 */
	public function getMessagesSince($fromUid, $toUid) {
		$query = new Horde_Imap_Client_Search_Query();
		$query->ids(new Horde_Imap_Client_Ids("$fromUid:$toUid"));
		return $this->getMessages(-1, -1, $query);
	}

	/**
	 * @return Horde_Imap_Client_Mailbox
	 */
	public function getHordeMailBox() {
		return $this->mailBox;
	}

}
