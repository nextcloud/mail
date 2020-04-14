<?php

declare(strict_types=1);

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

	/** @var IMAPMessage[] */
	private $newMessages;

	/** @var IMAPMessage[] */
	private $changedMessages;

	/** @var int[] */
	private $vanishedMessageUids;

	/**
	 * @param string $syncToken
	 * @param IMAPMessage[] $newMessages
	 * @param IMAPMessage[] $changedMessages
	 * @param int[] $vanishedMessageUids
	 */
	public function __construct(array $newMessages = [],
								array $changedMessages = [],
								array $vanishedMessageUids = []) {
		$this->newMessages = $newMessages;
		$this->changedMessages = $changedMessages;
		$this->vanishedMessageUids = $vanishedMessageUids;
	}

	/**
	 * @return IMAPMessage[]
	 */
	public function getNewMessages(): array {
		return $this->newMessages;
	}

	/**
	 * @return IMAPMessage[]
	 */
	public function getChangedMessages(): array {
		return $this->changedMessages;
	}

	/**
	 * @return int[]
	 */
	public function getVanishedMessageUids(): array {
		return $this->vanishedMessageUids;
	}

	public function jsonSerialize(): array {
		return [
			'newMessages' => $this->newMessages,
			'changedMessages' => $this->changedMessages,
			'vanishedMessages' => $this->vanishedMessageUids,
		];
	}

	public function merge(Response $other): self {
		return new self(
			array_merge($this->getNewMessages(), $other->getNewMessages()),
			array_merge($this->getChangedMessages(), $other->getChangedMessages()),
			array_merge($this->getVanishedMessageUids(), $other->getVanishedMessageUids())
		);
	}
}
