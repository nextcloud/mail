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


namespace OCA\Mail\Flow;

use OCA\Mail\Db\Message;
use OCA\Mail\Events\NewMessagesSynchronized;
use OCP\EventDispatcher\Event;
use OCP\WorkflowEngine\EntityContext\IDisplayName;
use OCP\WorkflowEngine\EntityContext\IDisplayText;
use OCP\WorkflowEngine\EntityContext\IUrl;
use OCP\WorkflowEngine\GenericEntityEvent;
use OCP\WorkflowEngine\IRuleMatcher;

class Mail implements \OCP\WorkflowEngine\IEntity, IDisplayName, IDisplayText, IUrl {
	private ?\OCA\Mail\Db\Message $message = null;

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'Mail';
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return \OC::$server->getURLGenerator()->imagePath('core', 'actions/mail.svg');
	}

	/**
	 * @inheritDoc
	 */
	public function getEvents(): array {
		return [
			new GenericEntityEvent('New mail', MailReceivedEntityEvent::class),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function prepareRuleMatcher(IRuleMatcher $ruleMatcher, string $eventName, Event $event): void {
		if (!$event instanceof MailReceivedEntityEvent) {
			return;
		}

		$ruleMatcher->setEntitySubject($this, $event->getMessage());
		$this->message = $event->getMessage();
	}

	/**
	 * @inheritDoc
	 */
	public function isLegitimatedForUserId(string $userId): bool {
		return true;
	}

	public function getDisplayName(): string {
		return $this->message->getSubject();
		// PreviewEnhancer could get us some more context here
	}

	public function getUrl(): string {
		return '/index.php/apps/mail/box/' . $this->message->getMailboxId() . '/' . $this->message->getThreadRootId();
	}

	public function getDisplayText(int $verbosity = 0): string {
		$from = $this->message->getFrom()->first();
		$message = 'Mail from ' . $from->getLabel() . ' ' . $from->getEmail() . PHP_EOL;
		$message .= $this->message->getSubject();
		return $message;
	}

	public function getMessage(): ?Message {
		return $this->message;
	}
}
