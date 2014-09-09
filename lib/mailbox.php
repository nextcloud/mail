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

class Mailbox {

	/**
	 * @var \Horde_Imap_Client_Socket
	 */
	private $conn;

	private $folderId;

	private $attributes;

	private $specialRole;

	private $delimiter;

	function __construct($conn, $folderId, $attributes, $delimiter='/') {
		$this->conn = $conn;
		$this->folderId = $folderId;
		$this->attributes = $attributes;
		$this->delimiter = $delimiter;
		$this->getSpecialRoleFromAttributes();
		if ($this->specialRole === null) {
			$this->guessSpecialRole();
		}
	}

	public function getMessages($from = 0, $count = 2) {
		$total = $this->getTotalMessages();
		$from = $total - $from;
		$to = max($from - $count, 1);

		$headers = array();

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->flags();
//		$fetch_query->seq();
		$fetch_query->size();
		$fetch_query->uid();
		$fetch_query->imapDate();
		$fetch_query->structure();

		$headers = array_merge($headers, array(
			'importance',
			'list-post',
			'x-priority'
		));
		$headers[] = 'content-type';

		$fetch_query->headers('imp', $headers, array(
			'cache' => true,
			'peek'  => true
		));

		$ids = new \Horde_Imap_Client_Ids("$from:$to", true);
		$options = array('ids' => $ids);
		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$headers = $this->conn->fetch($this->folderId, $fetch_query, $options);

		ob_start(); // fix for Horde warnings
		$messages = array();
		foreach ($headers->ids() as $message_id) {
			$header = $headers[$message_id];
			$message = new Message($this->conn, $this->folderId, $message_id, $header);
			$messages[] = $message->getListArray();
		}
		ob_clean();

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
		return new Message($this->conn, $this->folderId, $messageId, null, $loadHtmlMessageBody);
	}

	private function getStatus($flags = \Horde_Imap_Client::STATUS_ALL) {
		return $this->conn->status($this->folderId, $flags);
	}

	public function getTotalMessages() {
		$status = $this->getStatus(\Horde_Imap_Client::STATUS_MESSAGES);
		return $status['messages'];
	}

	public function getDisplayName() {
		return \Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($this->folderId);
	}
	
	public function getFolderId() {
		return $this->folderId;
	}

	public function getSpecialRole() {
		return $this->specialRole;
	}
	/**
	 * @return array
	 */
	public function getListArray() {
		$displayName = $this->getDisplayName();
		try {
			$status = $this->getStatus();
			$total = $status['messages'];
			$specialRole = $this->getSpecialRole();
			$unseen = ($specialRole === 'trash') ? 0 : $status['unseen'];
			$isEmpty = ($total === 0);
			return array(
				'id' => base64_encode($this->folderId),
				'name' => $displayName,
				'specialRole' => $specialRole,
				'unseen' => $unseen,
				'total' => $total,
				'isEmpty' => $isEmpty
			);
		} catch (\Horde_Imap_Client_Exception $e) {
			return array(
				'id' => base64_encode($this->folderId),
				'name' => $displayName,
				'specialRole' => null,
				'unseen' => 0,
				'total' => 0,
				'error' => $e->getMessage(),
				'isEmpty' => true
			);
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
			'drafts'   => array('draft', 'drafts'),
			'archive' => array('archive', 'archives'),
			'trash'   => array('deleted messages', 'trash'),
			'junk'    => array('junk', 'spam'),
		);
		
		$lowercaseId = strtolower(reset(explode($this->delimiter, $this->folderId, 2)));
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
	 */
	public function deleteMessage($messageId) {
		$dest = "Trash";
		$ids = new \Horde_Imap_Client_Ids($messageId);
		$result = $this->conn->copy($this->folderId, $dest, array('move' => true, 'ids' => $ids));
		\OC::$server->getLogger()->info("Message deleted: {result}", array('result' => $result));
	}

	/**
	 * @param int $messageId
	 * @param int $attachmentId
	 * @return Attachment
	 */
	public function getAttachment($messageId, $attachmentId) {
		return new Attachment($this->conn, $this->folderId, $messageId, $attachmentId);
	}

	/**
	 * @param string $rawBody
	 */
	public function saveMessage($rawBody) {

		$this->conn->append($this->folderId, array(
			array(
				'data' => $rawBody,
				'flags' => array(Horde_Imap_Client::FLAG_SEEN)
			)
		));
	}

	/**
	 * @param int $uid
	 * @param string $flag
	 * @param boolean $add
	 */
	public function setMessageFlag($uid, $flag, $add) {

		$options = array(
			'ids' => new Horde_Imap_Client_Ids($uid)
		);
		if ($add) {
			$options['add'] = array($flag);
		} else {
			$options['remove'] = array($flag);
		}
		$this->conn->store($this->folderId, $options);
	}
}

