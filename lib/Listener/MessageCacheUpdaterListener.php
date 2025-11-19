<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event>
 */
class MessageCacheUpdaterListener implements IEventListener {
	public function __construct(
		private readonly \OCA\Mail\Db\MessageMapper $mapper,
		private readonly \Psr\Log\LoggerInterface $logger
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof MessageFlaggedEvent) {
			$messages = $this->mapper->findByUids($event->getMailbox(), [$event->getUid()]);
			$message = reset($messages);

			if ($message === false) {
				$this->logger->warning('Flagged message is not cached');
				return;
			}

			$message->setFlag($event->getFlag(), $event->isSet());

			$this->mapper->update($message);
		} elseif ($event instanceof MessageDeletedEvent) {
			$this->mapper->deleteByUid(
				$event->getMailbox(),
				$event->getMessageId()
			);
		}
	}
}
