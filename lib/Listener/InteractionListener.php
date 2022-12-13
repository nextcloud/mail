<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Address;
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
		$recipients = $event->getMessage()->getTo()
			->merge($event->getMessage()->getCC())
			->merge($event->getMessage()->getBCC());
		foreach ($recipients->iterate() as $recipient) {
			/** @var Address $recipient */
			$interactionEvent = new ContactInteractedWithEvent($user);
			$email = $recipient->getEmail();
			if ($email === null) {
				// Weird, bot ok
				continue;
			}
			$interactionEvent->setEmail($email);
			$this->dispatcher->dispatch(ContactInteractedWithEvent::class, $interactionEvent);
		}
	}
}
