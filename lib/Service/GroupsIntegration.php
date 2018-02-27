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

use OCA\Mail\Service\Group\IGroupService;

class GroupsIntegration {

	/**
	 * @var IGroupService
	 */
	private $groupServices = [];

	public function __construct(IGroupService ...$groupServices) {
		$this->groupServices = $groupServices;
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $term
	 * @return array
	 */
	public function getMatchingGroups($term) {
		$receivers = [];
		foreach ($this->groupServices as $gs) {
      $result = $gs->search($term);
			foreach($result as $g) {
				$receivers[] = [
					'id' => $g['id'],
					'label' => $g['name'],
					'value' => $g['id'],
					'photo' => null,
				];
			}
		}

		return $receivers;
	}


}
