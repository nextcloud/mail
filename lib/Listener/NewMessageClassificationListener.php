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

use OCA\Mail\Events\NewMessagesSynchronized;
use OCA\Mail\Service\Classification\MessageClassifier;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class NewMessageClassificationListener implements IEventListener {

	/** @var MessageClassifier */
	private $classifier;

	public function __construct(MessageClassifier $classifier) {
		$this->classifier = $classifier;
	}

	public function handle(Event $event): void {
		if (!($event instanceof NewMessagesSynchronized)) {
			return;
		}

		foreach ($event->getMessages() as $message) {
			if ($this->classifier->isImportant($event->getAccount(), $event->getMailbox(), $message)) {
				$message->setFlagImportant(true);
			}
		}
	}
}
