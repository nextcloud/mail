<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

class NewMessageReceivedEvent extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private Account $account,
		private Mailbox $mailbox,
		private Message $message,
		private string $uri,
	) {
		parent::__construct();
	}

	public function getUri(): string {
		return $this->uri;
	}

	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'accountId' => $this->account->getId(),
			'inReplyToRfcMessageId' => $this->message->getInReplyTo(),
			'mailboxId' => $this->mailbox->getId(),
			'messageId' => $this->message->getId(),
			'messageUri' => $this->uri,
			'rfcMessageId' => $this->message->getMessageId(),
			'sentAt' => $this->message->getSentAt(),
			'subject' => $this->message->getSubject(),
			'threadRootId' => $this->message->getThreadRootId(),
		];
	}
}
