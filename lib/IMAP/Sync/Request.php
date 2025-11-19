<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Sync;

final class Request {
	/**
	 * @param int[] $uids
	 */
	public function __construct(
		private readonly string $id,
		private readonly string $mailbox,
		private readonly string $syncToken,
		private readonly array $uids
	) {
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
