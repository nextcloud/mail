<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
			$retention->setMessageId($message->getMessageId());
			$retention->setKnownSince($now);
			$this->messageRetentionMapper->insert($retention);
		}
	}
}
