<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Sync;

class Request {
	private string $id;

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
	public function __construct(string $id, string $mailbox, string $syncToken, array $uids) {
		$this->id = $id;
		$this->mailbox = $mailbox;
		$this->syncToken = $syncToken;
		$this->uids = $uids;
	}

	/**
	 * Get the id of this request which stays constant for all requests while syncing a single mailbox
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Get the mailbox name
	 */
	public function getMailbox(): string {
		return $this->mailbox;
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
