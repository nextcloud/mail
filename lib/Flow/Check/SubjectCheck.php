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


namespace OCA\Mail\Flow\Check;

use OCA\Mail\Db\Message;
use OCA\Mail\Flow\Mail;
use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityCheck;

class SubjectCheck extends AbstractStringCheck implements IEntityCheck {

	private ?Message $message = null;
	/**
	 * @inheritDoc
	 */
	public function setEntitySubject(IEntity $entity, $subject): void {
		if (!$entity instanceof Mail) {
			return;
		}

		$this->message = $subject;
	}

	protected function getActualValue() {
		return $this->message->getSubject() ?? null;
	}

	public function supportedEntities(): array {
		return [ Mail::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
