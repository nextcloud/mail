<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use JsonSerializable;
use ReturnTypeWillChange;

final class MailboxStats implements JsonSerializable {
	private int $total;
	private int $unread;

	public function __construct(int $total, int $unread) {
		$this->total = $total;
		$this->unread = $unread;
	}

	public function getTotal(): int {
		return $this->total;
	}

	public function getUnread(): int {
		return $this->unread;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'total' => $this->total,
			'unread' => $this->unread,
		];
	}
}
