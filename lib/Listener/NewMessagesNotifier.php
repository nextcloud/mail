<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\NewMessageReceivedEvent;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IURLGenerator;

/**
 * @template-implements IEventListener<Event|NewMessagesSynchronized>
 */
class NewMessagesNotifier implements IEventListener {

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IURLGenerator $urlGenerator,
	) {
	}
	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof NewMessagesSynchronized) {
			return;
		}

		foreach ($event->getMessages() as $message) {
			$uri = $this->urlGenerator->linkToOCSRouteAbsolute('mail.messageApi.get', ['id' => $message->getId()]);
			$this->eventDispatcher->dispatchTyped(new NewMessageReceivedEvent($uri));
		}
	}
}
