<?php
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OCA\Mail\Listener;

use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Flow\Check\SubjectCheck;
use OCA\Mail\Flow\FileOperation;
use OCA\Mail\Flow\Mail;
use OCA\Mail\Flow\MailReceivedEntityEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\WorkflowEngine\Events\RegisterChecksEvent;
use OCP\WorkflowEngine\Events\RegisterEntitiesEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class RegisterEntitiesListener implements \OCP\EventDispatcher\IEventListener {

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if ($event instanceof RegisterEntitiesEvent) {
			$event->registerEntity(new Mail());
			return;
		}

		if ($event instanceof RegisterChecksEvent) {
			$event->registerCheck(new SubjectCheck(\OC::$server->getL10N('mail')));
			return;
		}

		if ($event instanceof RegisterOperationsEvent) {
			$event->registerOperation(\OCP\Server::get(FileOperation::class));
			return;
		}
		if ($event instanceof NewMessagesSynchronized) {
			$userId = $event->getAccount()->getUserId();
			$eventDispatcher = \OCP\Server::get(IEventDispatcher::class);

			foreach ($event->getMessages() as $message) {
				try {
					$event = new MailReceivedEntityEvent($event->getAccount(), $event->getMailbox(), $message);
				} catch (\Throwable $e) {
					throw $e;
				}
				$eventDispatcher->dispatchTyped($event);
			}
		}

	}
}
