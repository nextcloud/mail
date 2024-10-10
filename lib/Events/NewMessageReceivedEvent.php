<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Events;

use OCP\EventDispatcher\Event;

class NewMessageReceivedEvent extends Event {
	public function __construct(
		private string $uri,
	) {
		parent::__construct();
	}

	public function getUri(): string {
		return $this->uri;
	}
}
