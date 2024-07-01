<?php

declare(strict_types=1);

/**
 * @author Matthias Rella <mrella@pisys.eu>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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

use OCA\Mail\Exception\ServiceException;
use OCP\IConfig;
use OCP\IGroupManager;

class NextcloudGroupService implements IGroupService {
	/**
	 * Nextcloud's group manager
	 *
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Group's namespace
	 *
	 * @var string
	 */
	private $namespace = "Nextcloud";

	public function __construct(IGroupManager $groupManager, IConfig $config) {
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	public function getNamespace(): string {
		return $this->namespace;
	}

	public function search(string $term): array {
		$c1 = $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes');
		$c2 = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no');
		if ($c1 !== 'yes'
			|| $c2 !== 'no') {
			return [];
		}
		$groups = $this->groupManager->search($term);

		return array_map(
			static function ($g) {
				return [
					'id' => $g->getGID(),
					'name' => $g->getDisplayName()
				];
			},
			$groups
		);
	}

	public function getUsers(string $groupId): array {
		if (!$this->groupManager->groupExists($groupId)) {
			throw new ServiceException("$groupId ({$this->getNamespace()}) does not exist");
		}
		$users = $this->groupManager->get($groupId)->getUsers();
		return array_map(
			static function ($user) {
				return [
					'id' => $user->getUID(),
					'name' => $user->getDisplayName(),
					'email' => $user->getEMailAddress()
				];
			},
			$users
		);
	}
}
