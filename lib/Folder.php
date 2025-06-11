<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

use Horde_Imap_Client_Mailbox;

class Folder {

	/** @var Horde_Imap_Client_Mailbox */
	private $mailbox;

	/** @var array */
	private $attributes;

	/** @var string */
	private $delimiter;

	/** @var null|array */
	private $status;

	/** @var string[] */
	private $specialUse;

	private ?string $myAcls;

	public function __construct(Horde_Imap_Client_Mailbox $mailbox,
		array $attributes,
		?string $delimiter,
		?array $status) {
		$this->mailbox = $mailbox;
		$this->attributes = $attributes;
		$this->delimiter = $delimiter;
		$this->status = $status;
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
	 * @return null|array
	 */
	public function getStatus(): ?array {
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
