<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Db\MessageRetention;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|NewMessagesSynchronized>
 */
class MessageKnownSinceListener implements IEventListener {

	public function __construct(
		private MessageRetentionMapper $messageRetentionMapper,
		private ITimeFactory $timeFactory,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		$trashRetention = $event->getAccount()->getMailAccount()->getTrashRetentionDays();
		if ($trashRetention === null) {
			return;
		}

		$trashMailboxId = $event->getAccount()->getMailAccount()->getTrashMailboxId();
		if ($trashMailboxId === null) {
			return;
		}

		$now = $this->timeFactory->getTime();
		foreach ($event->getMessages() as $message) {
			if ($message->getMailboxId() !== $trashMailboxId) {
				continue;
			}

			$retention = new MessageRetention();
			$retention->setMailboxId($message->getMailboxId());
			$retention->setUid($message->getUid());
			$retention->setKnownSince($now);
			$this->messageRetentionMapper->insert($retention);
		}
	}
}
