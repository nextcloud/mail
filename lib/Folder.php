<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

class Folder {

	/** @var string[] */
	private array $specialUse;

	private ?string $myAcls;

	public function __construct(
		private readonly \Horde_Imap_Client_Mailbox $mailbox,
		private readonly array $attributes,
		private readonly ?string $delimiter,
		private ?array $status
	) {
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

	public function getStatus(): ?array {
		return $this->status;
	}

	public function setStatus(array $status): void {
		$this->status = $status;
	}

	/**
	 * @param string $use
	 */
	public function addSpecialUse($use): void {
		$this->specialUse[] = $use;
	}

	/**
	 * @return string[]
	 */
	public function getSpecialUse(): array {
		return $this->specialUse;
	}

	public function setMyAcls(?string $acls): void {
		$this->myAcls = $acls;
	}

	public function getMyAcls(): ?string {
		return $this->myAcls;
	}
}
