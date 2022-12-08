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

namespace OCA\Mail;

use Horde_Imap_Client_Mailbox;

class Folder {
	/** @var int */
	private $accountId;

	/** @var Horde_Imap_Client_Mailbox */
	private $mailbox;

	/** @var array */
	private $attributes;

	/** @var string */
	private $delimiter;

	/** @var array */
	private $status;

	/** @var string[] */
	private $specialUse;

	private ?string $myAcls;

	public function __construct(int $accountId, Horde_Imap_Client_Mailbox $mailbox, array $attributes, ?string $delimiter) {
		$this->accountId = $accountId;
		$this->mailbox = $mailbox;
		$this->attributes = $attributes;
		$this->delimiter = $delimiter;
		$this->status = [];
		$this->specialUse = [];
		$this->myAcls = null;
	}

	/**
	 * @return string
	 */
	public function getMailbox() {
		return $this->mailbox->utf8;
	}

	public function getDelimiter(): ?string {
		return $this->delimiter;
	}

	public function getAttributes(): array {
		return $this->attributes;
	}

	/**
	 * @return array
	 */
	public function getStatus(): array {
		return $this->status;
	}

	/**
	 * @param array $status
	 *
	 * @return void
	 */
	public function setStatus(array $status): void {
		$this->status = $status;
	}

	/**
	 * @param string $use
	 *
	 * @return void
	 */
	public function addSpecialUse($use): void {
		$this->specialUse[] = $use;
	}

	/**
	 * @return string[]
	 */
	public function getSpecialUse() {
		return $this->specialUse;
	}

	public function setMyAcls(?string $acls) {
		$this->myAcls = $acls;
	}

	public function getMyAcls(): ?string {
		return $this->myAcls;
	}
}
