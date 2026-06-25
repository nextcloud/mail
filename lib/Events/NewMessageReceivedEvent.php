<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Events;

use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

class NewMessageReceivedEvent extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private string $uri,
		private Message $message,
	) {
		parent::__construct();
	}

	public function getUri(): string {
		return $this->uri;
	}

	public function getWebhookSerializable(): array {
		return [
			'messageUri' => $this->uri,
			'message' => $this->message,
		];
	}
}
