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

namespace OCA\Mail;

use Horde_Imap_Client_Mailbox;
use JsonSerializable;

class Folder implements JsonSerializable {

	/** @var int */
	private $accountId;

	/** @var Horde_Imap_Client_Mailbox */
	private $mailbox;

	/** @var array */
	private $attributes;

	/** @var string */
	private $delimiter;

	/** @var Folder[] */
	private $folders;

	/** @var array */
	private $status;

	/** @var string[] */
	private $specialUse;

	/** @var string */
	/**
	 * @param Account $account
	 * @param Horde_Imap_Client_Mailbox $mailbox
	 * @param array $attributes
	 * @param string $delimiter
	 */
	public function __construct(int $accountId, Horde_Imap_Client_Mailbox $mailbox, array $attributes, $delimiter) {
		$this->accountId = $accountId;
		$this->mailbox = $mailbox;
		$this->attributes = $attributes;
		$this->delimiter = $delimiter;
		$this->folders = [];
		$this->status = [];
		$this->specialUse = [];
	}

	/**
	 * @return string
	 */
	public function getMailbox() {
		return $this->mailbox->utf8;
	}

	/**
	 * @return string
	 */
	public function getDelimiter() {
		return $this->delimiter;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param Folder $folder
	 *
	 * @return void
	 */
	public function addFolder(Folder $folder): void {
		$this->folders[$folder->getMailbox()] = $folder;
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

	/**
	 * @return Folder[]
	 */
	public function getFolders() {
		return $this->folders;
	}

	/**
	 * @return boolean
	 */
	public function isSearchable() {
		return !in_array('\noselect', $this->getAttributes());
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		$folders = [];
		foreach ($this->folders as $folder) {
			$folders[$folder->getMailbox()] = $folder->jsonSerialize();
		}
		return [
			'id' => base64_encode($this->getMailbox()),
			'accountId' => $this->accountId,
			'displayName' => $this->getMailbox(),
			'unseen' => isset($this->status['unseen']) ? $this->status['unseen'] : 0,
			'total' => isset($this->status['messages']) ? (int) $this->status['messages'] : 0,
			'isEmpty' => isset($this->status['messages']) ? 0 >= (int) $this->status['messages'] : true,
			'noSelect' => !$this->isSelectable(),
			'attributes' => $this->attributes,
			'delimiter' => $this->delimiter,
			'folders' => array_values($folders),
			'specialUse' => $this->specialUse,
			'specialRole' => empty($this->specialUse) ? null : $this->specialUse[0],
		];
	}

	public function isSelectable(): bool {
		return !in_array('\noselect', $this->attributes);
	}
}
