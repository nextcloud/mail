<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\IMAP\Sync;

use JsonSerializable;
use OCA\Mail\IMAP\MailboxStats;
use ReturnTypeWillChange;

/**
 * @psalm-template T
 */
final class Response implements JsonSerializable {
	/**
	 * @param T[] $newMessages
	 * @param T[] $changedMessages
	 * @param int[] $vanishedMessageUids
	 * @param MailboxStats|null $stats
	 */
	public function __construct(
		private array $newMessages,
		private array $changedMessages,
		private array $vanishedMessageUids,
		private ?MailboxStats $stats = null,
	) {
	}

	/**
	 * @return T[]
	 */
	public function getNewMessages(): array {
		return $this->newMessages;
	}

	/**
	 * @return T[]
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

	public function getStats(): ?MailboxStats {
		return $this->stats;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'newMessages' => $this->newMessages,
			'changedMessages' => $this->changedMessages,
			'vanishedMessages' => $this->vanishedMessageUids,
			'stats' => $this->stats,
		];
	}
}
