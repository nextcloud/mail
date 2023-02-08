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

use OCP\EventDispatcher\Event;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IRuleMatcher;

class FileOperation implements \OCP\WorkflowEngine\ISpecificOperation {

	private IL10N $l10n;
	private IURLGenerator $urlGenerator;

	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @inheritDoc
	 */
	public function getDisplayName(): string {
		return 'Save to files';
		// TODO: Implement getDisplayName() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string {
		return 'Store a mail as a a file';
		// TODO: Implement getDescription() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'filetypes/folder.svg');
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function validateOperation(string $name, array $checks, string $operation): void {
		// TODO: Implement validateOperation() method.
		$myname = $name;
	}

	/**
	 * @inheritDoc
	 */
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		$flows = $ruleMatcher->getFlows(false);
		foreach ($flows as $flow) {
			$entity = $ruleMatcher->getEntity();
			if (!$entity instanceof Mail) {
				continue;
			}

			if (!$event instanceof MailReceivedEntityEvent) {
				continue;
			}
			$userId = $event->getAccount()->getUserId();

			$message = $entity->getMessage();

			$rootFolder = \OCP\Server::get(IRootFolder::class);
			$userFolder = $rootFolder->getUserFolder($userId);
			$file = $userFolder->newFile($message->getMessageId() . '.json');
			$file->putContent(json_encode($message->jsonSerialize()));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getEntityId(): string {
		return Mail::class;
	}
}
