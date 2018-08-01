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

class Request {

	/** @var string */
	private $mailbox;

	/** @var string */
	private $syncToken;

	/** @var array */
	private $uids;

	/**
	 * @param string $mailbox
	 * @param string $syncToken
	 * @param int[] $uids
	 */
	public function __construct(string $mailbox, string $syncToken, array $uids) {
		$this->mailbox = $mailbox;
		$this->syncToken = $syncToken;
		$this->uids = $uids;
	}

	/**
	 * Get the mailbox name
	 */
	public function getMailbox(): string {
		// TODO: this is kinda hacky
		$parts = explode('/', $this->mailbox);
		if (count($parts) > 1 && $parts[count($parts) - 1] === 'FLAGGED') {
			return implode('/', array_slice($parts, 0, count($parts) - 1));
		}
		return $this->mailbox;
	}

	public function isFlaggedMailbox(): bool {
		// TODO: this is kinda hacky
		return $this->mailbox !== $this->getMailbox();
	}

	/**
	 * @return string the Horde sync token
	 */
	public function getToken(): string {
		return $this->syncToken;
	}

	/**
	 * Get an array of known uids on the client-side
	 *
	 * @return int[]
	 */
	public function getUids(): array {
		return $this->uids;
	}

}
