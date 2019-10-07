<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
	 * @return string
	 */
	public function getFolderId(): string {
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
	public function getStatus(int $flags = Horde_Imap_Client::STATUS_ALL): array {
		$status = parent::getStatus($flags);
		if (isset($status['unseen'])) {
			$status['unseen'] = 0;
		}

		return $status;
	}
}
