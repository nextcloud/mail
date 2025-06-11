<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\MessageSentEvent;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use function class_exists;

/**
 * @template-implements IEventListener<Event|MessageSentEvent>
 */
class InteractionListener implements IEventListener {
	/** @var IEventDispatcher */
	private $dispatcher;

	/** @var IUserSession */
	private $userSession;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IEventDispatcher $dispatcher,
		IUserSession $userSession,
		LoggerInterface $logger) {
		$this->dispatcher = $dispatcher;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof MessageSentEvent)) {
			return;
		}
		if (!class_exists(ContactInteractedWithEvent::class)) {
			$this->logger->debug(ContactInteractedWithEvent::class . ' does not exist, ignoring the event');
			return;
		}
		if (($user = $this->userSession->getUser()) === null) {
			$this->logger->debug('no user object found');
			return;
		}
		$message = $event->getLocalMessage();
		$emails = [];
		foreach ($message->getRecipients() as $recipient) {
			if (in_array($recipient->getEmail(), $emails)) {
				continue;
			}
			$interactionEvent = new ContactInteractedWithEvent($user);
			$email = $recipient->getEmail();
			if ($email === null) {
				// Weird, bot ok
				continue;
			}
			$emails[] = $email;
			$interactionEvent->setEmail($email);
			$this->dispatcher->dispatch(ContactInteractedWithEvent::class, $interactionEvent);
		}
	}
}
