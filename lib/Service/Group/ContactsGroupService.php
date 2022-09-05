<?php

declare(strict_types=1);

/**
 * @author Matthias Rella <mrella@pisys.eu>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Boris Fritscher <boris.fritscher@gmail.com>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\Group;

use OCP\Contacts\IManager;
use OCP\IConfig;

class ContactsGroupService implements IGroupService {
	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	/**
	 * Group's namespace
	 *
	 * @var string
	 */
	private $namespace = "Contacts";

	public function __construct(IManager $contactsManager, IConfig $config) {
		$this->contactsManager = $contactsManager;
		$this->config = $config;
	}

	public function getNamespace(): string {
		return $this->namespace;
	}

	public function search(string $term): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$allowSystemUsers = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') === 'yes';

		$result = $this->contactsManager->search($term, ['CATEGORIES']);
		$receivers = [];
		foreach ($result as $r) {
			if (!$allowSystemUsers && isset($r['isLocalSystemBook']) && $r['isLocalSystemBook']) {
				continue;
			}
			if (!isset($r['EMAIL'])) {
				continue;
			}
			foreach (explode(',', $r['CATEGORIES'] ?? '') as $group) {
				$receivers[] = [
					'id' => $group,
					'name' => $group
				];
			}
		}
		return array_unique($receivers, SORT_REGULAR);
	}

	public function getUsers(string $groupId): array {
		if (!$this->contactsManager->isEnabled()) {
			return [];
		}

		// If 'Allow username autocompletion in share dialog' is disabled in the admin sharing settings, then we must not
		// auto-complete system users
		$allowSystemUsers = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'no') === 'yes';

		$result = $this->contactsManager->search($groupId, ['CATEGORIES']);
		$receivers = [];
		foreach ($result as $r) {
			if (!$allowSystemUsers && isset($r['isLocalSystemBook']) && $r['isLocalSystemBook']) {
				continue;
			}
			if (!isset($r['EMAIL'])) {
				continue;
			}
			$groups = explode(',', $r['CATEGORIES']);

			// search matches substring but we only want full match
			if (!in_array($groupId, $groups)) {
				continue;
			}

			$emails = $r['EMAIL'];
			if (!is_array($emails)) {
				$emails = [$emails];
			}
			foreach ($emails as $email) {
				$receivers[] = [
					'email' => $email
				];
			}
		}
		return $receivers;
	}
}
