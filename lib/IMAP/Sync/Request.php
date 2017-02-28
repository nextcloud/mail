<?php

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

namespace OCA\Mail\IMAP\Sync;

class Request {

	/** @var string */
	private $mailbox;

	/** @var string */
	private $syncToken;

	/**
	 * @param string $mailbox
	 * @param string $syncToken
	 */
	public function __construct($mailbox, $syncToken) {
		$this->mailbox = $mailbox;
		$this->syncToken = $syncToken;
	}

	/**
	 * Get the mailbox name
	 *
	 * @return string
	 */
	public function getMailbox() {
		return $this->mailbox;
	}

	/**
	 * @return string the Horde sync token
	 */
	public function getToken() {
		return $this->syncToken;
	}

}
