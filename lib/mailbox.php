<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use Horde_Imap_Client;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;

class Mailbox {

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
	function __construct($conn, $mailBox, $attributes, $delimiter='/') {
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

	public function getMessages($from = 0, $count = 2, $filter = '') {

		if ($filter instanceof Horde_Imap_Client_Search_Query) {
			$query = $filter;
		} else {
			$query = new Horde_Imap_Client_Search_Query();
			if ($filter) {
				$query->text($filter, false);
			}
		}
		$result = $this->conn->search($this->mailBox, $query, ['sort' => [Horde_Imap_Client::SORT_DATE]]);
		$ids = array_reverse($result['match']->ids);
		if ($from >= 0 && $count >= 0) {
			$ids = array_slice($ids, $from, $count);
		}
		$ids = new \Horde_Imap_Client_Ids($ids, false);

		$headers = [];

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
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
			$message = new Message($this->conn, $this->mailBox, $message_id, $header);
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
	 * @param $messageId
	 * @return Message
	 */
	public function getMessage($messageId, $loadHtmlMessageBody = false) {
		return new Message($this->conn, $this->mailBox, $messageId, null, $loadHtmlMessageBody);
	}

	public function getStatus($flags = \Horde_Imap_Client::STATUS_ALL) {
		return $this->conn->status($this->mailBox, $flags);
	}

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
		$folderId = $this->mailBox->utf8;

		if (strlen($folderId) > 6 && strpos($folderId, 'INBOX' . $this->delimiter) === 0) {
			return substr($folderId, 6);
		}

		return $folderId;
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

	public function getSpecialRole() {
		return $this->specialRole;
	}

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
				'uidnext' => $status['uidnext']
			];
		} catch (\Horde_Imap_Client_Exception $e) {
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
			$specialUseAttributes = array(
				strtolower(Horde_Imap_Client::SPECIALUSE_ALL),
				strtolower(Horde_Imap_Client::SPECIALUSE_ARCHIVE),
				strtolower(Horde_Imap_Client::SPECIALUSE_DRAFTS),
				strtolower(Horde_Imap_Client::SPECIALUSE_FLAGGED),
				strtolower(Horde_Imap_Client::SPECIALUSE_JUNK),
				strtolower(Horde_Imap_Client::SPECIALUSE_SENT),
				strtolower(Horde_Imap_Client::SPECIALUSE_TRASH)
			);

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
		
		$specialFoldersDict = array(
			'inbox'   => array('inbox'),
			'sent'    => array('sent', 'sent items', 'sent messages', 'sent-mail'),
			'drafts'  => array('draft', 'drafts'),
			'archive' => array('archive', 'archives'),
			'trash'   => array('deleted messages', 'trash'),
			'junk'    => array('junk', 'spam'),
		);
		
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

	public function getMessagesSince($fromUid, $toUid) {
		$query = new Horde_Imap_Client_Search_Query();
//		$query->flag('SEEN', false);
		$query->ids(new Horde_Imap_Client_Ids("$fromUid:$toUid"));
		return $this->getMessages(-1, -1, $query);
	}
}

