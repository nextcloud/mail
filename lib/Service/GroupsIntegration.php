<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Matthias Rella <git@myrho.net>
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

use OCP\GroupInterface;

class GroupsIntegration {

	/**
	 * @var GroupInterface
	 */
	private $groupInterface;

	/**
	 * @param GroupInterface $groupsInterface
	 */
	public function __construct(GroupInterface $groupInterface) {
		$this->groupInterface = $groupInterface;
	}

	/**
	 * Extracts all matching contacts with email address and name
	 *
	 * @param string $term
	 * @return array
	 */
	public function getMatchingGroups($term) {
		$result = $this->groupInterface->getGroups($term);
		$receivers = [];
		foreach ($result as $id) {
				$receivers[] = [
					'id' => $id,
					'label' => $id,
					'value' => $id,
					'photo' => null,
				];
			}
		}

		return $receivers;
	}


}
