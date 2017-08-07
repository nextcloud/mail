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

use JsonSerializable;
use OCA\Mail\Model\IMAPMessage;

class Response implements JsonSerializable {

	/** @var string */
	private $syncToken;

	/** @var IMAPMessage[] */
	private $newMessages;

	/** @var IMAPMessage[] */
	private $changedMessages;

	/** @var array */
	private $vanishedMessages;

	/**
	 * @param string $syncToken
	 * @param IMAPMessage[] $newMessages
	 * @param IMAPMessage[] $changedMessages
	 * @param array $vanishedMessages
	 */
	public function __construct($syncToken, array $newMessages = [], array $changedMessages = [],
		array $vanishedMessages = []) {
		$this->syncToken = $syncToken;
		$this->newMessages = $newMessages;
		$this->changedMessages = $changedMessages;
		$this->vanishedMessages = $vanishedMessages;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'newMessages' => $this->newMessages,
			'changedMessages' => $this->changedMessages,
			'vanishedMessages' => $this->vanishedMessages,
			'token' => $this->syncToken,
		];
	}

}
