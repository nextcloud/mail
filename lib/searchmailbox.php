<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use Horde_Imap_Client;

class SearchMailbox extends Mailbox {

	/**
	 * @param Horde_Imap_Client_Socket $conn
	 * @param Horde_Imap_Client_Mailbox $mailBox
	 * @param array $attributes
	 * @param string $delimiter
	 */
	public function __construct($conn, $mailBox, $attributes, $delimiter = '/') {
		$attributes[] = Horde_Imap_Client::SPECIALUSE_FLAGGED;
		parent::__construct($conn, $mailBox, $attributes, $delimiter);
	}

	/**
	 * @param int $from
	 * @param int $count
	 * @param string $filter
	 * @return mixed
	 */
	public function getMessages($from = 0, $count = 2, $filter = '') {
		$query = new Horde_Imap_Client_Search_Query();
		$query->flag('FLAGGED');
		if ($filter) {
			$query->text($filter, false);
		}

		return parent::getMessages($from, $count, $query);
	}

	/**
	 * @return string
	 */
	public function getFolderId() {
		return parent::getFolderId() . '/FLAGGED';
	}

	/**
	 * @return null
	 */
	public function getParent() {
		return null;
	}

	/**
	 * @param int $flags
	 * @return mixed
	 */
	public function getStatus($flags = \Horde_Imap_Client::STATUS_ALL) {
		$status = parent::getStatus($flags);
		$status['unseen'] = 0;

		return $status;
	}
}
