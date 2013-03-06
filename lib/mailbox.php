<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

class Mailbox {

	/**
	 * @var \Horde_Imap_Client_Socket
	 */
	private $conn;

	private $folder_id;

	// input $conn = IMAP conn, $folder_id = folder id
	function __construct($conn, $folder_id) {
		$this->conn = $conn;
		$this->folder_id = $folder_id;
	}

	public function getMessages($from = 0, $count = 2) {
		$total = $this->getTotalMessages();
		if (($from + $count) > $total) {
			$count = $total - $from;
		}

		$headers = array();

		$fetch_query = new \Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->flags();
		$fetch_query->seq();
		$fetch_query->size();
		$fetch_query->uid();
		$fetch_query->imapDate();

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

		$opt = array('ids' => ($from + 1) . ':' . ($from + 1 + $count));
		$opt = array();
		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$headers = $this->conn->fetch($this->folder_id, $fetch_query, $opt);

		ob_start(); // fix for Horde warnings
		$messages = array();
		foreach ($headers as $message_id => $header) {
			$message = new Message($this->conn, $this->folder_id, $message_id);
			$message->setInfo($header);
			$messages[] = $message->getListArray();
		}
		ob_clean();
		return $messages;
	}

	/**
	 * @param $message_id
	 * @return Message
	 */
	public function getMessage($message_id) {
		return new Message($this->conn, $this->folder_id, $message_id);
	}

	private function getStatus($flags = \Horde_Imap_Client::STATUS_ALL) {
		return $this->conn->status($this->folder_id, $flags);
	}

	public function getTotalMessages() {
		$status = $this->getStatus(\Horde_Imap_Client::STATUS_MESSAGES);
		return $status['messages'];
	}

	public function getDisplayName() {
		return \Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($this->folder_id);
	}

	/**
	 * @return array
	 */
	public function getListArray() {
		$display_name = $this->getDisplayName();
		try {
			$status = $this->getStatus();
			return array('id' => $this->folder_id, 'name' => $display_name, 'unseen' => $status['unseen'], 'total' => $status['messages']);
		} catch (\Horde_Imap_Client_Exception $e) {
			return array('id' => $this->folder_id, 'name' => $display_name, 'unseen' => 0, 'total' => 0, 'error' => $e->getMessage());
		}
	}

}
