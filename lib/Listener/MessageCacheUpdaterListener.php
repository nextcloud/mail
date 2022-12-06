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

use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<Event>
 */
class MessageCacheUpdaterListener implements IEventListener {
	/** @var MessageMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MessageMapper $mapper,
								LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof MessageFlaggedEvent) {
			$messages = $this->mapper->findByUids($event->getMailbox(), [$event->getUid()]);
			$message = reset($messages);

			if ($message === false) {
				$this->logger->warning("Flagged message is not cached");
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
