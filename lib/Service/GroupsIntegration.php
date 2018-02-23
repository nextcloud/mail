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

namespace OCA\Mail\Service;

use OCP\IGroupManager;

class GroupsIntegration {

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @param IGroupManager $groupsManager
	 */
	public function __construct(IGroupManager $groupManager) {
		$this->groupManager = $groupManager;
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $term
	 * @return array
	 */
	public function getMatchingGroups($term) {
		$result = $this->groupManager->search($term);
		$receivers = [];
		foreach ($result as $g) {
				$receivers[] = [
					'id' => $g->getGID(),
					'label' => $g->getDisplayName(),
					'value' => $g->getDisplayName(),
					'photo' => null,
				];
    }

		return $receivers;
	}


}
