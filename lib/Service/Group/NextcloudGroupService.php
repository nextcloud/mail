<?php

/**
 * @author Matthias Rella <mrella@pisys.eu>
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

use OCP\IGroupManager;

class NextcloudGroupService implements IGroupService {

	/**
	 * Nextcloud's group manager
	 */
	private $groupManager;

	/**
	 * Group's display namespace
	 */
	private $namespace = "Nextcloud";

	/** 
	 * Group's namespace id
	 */
	private $namespaceId = "nextcloud";

	public function __construct(IGroupManager $groupManager) {
		$this->groupManager = $groupManager;
	}

	public function getNamespace() {
		return $this->namespace;
	}

	public function getNamespaceId() {
		return $this->namespaceId;
	}

	/**
	 * @param string $term
	 * @return \OCA\Mail\Service\Group\Group[]
	 */
	public function search($term) {
		$groups = $this->groupManager->search($term);

		return array_map(
			function($g) {
				return [
					'id' => $this->getNamespaceId() . ":" . $g->getGID(),
					'name' => $g->getDisplayName() . " (" . $this->getNamespace() . ")"
				];
      },
      $groups
		);

	}
}
