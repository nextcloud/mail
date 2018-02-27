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

abstract class AbstractGroupService {

	/**
	 * Search the service's groups.
	 *
	 * @param string $term
	 * @return string
	 */
	abstract public function search($term);

	/**
	 * Get the group's namespace.
	 *
	 * @return string
	 */
	abstract public function getNamespace();

	/**
	 * Get the group's users.
	 *
	 * @param string $groupId 
	 * @return array with group's key-value pairs
	 */
	abstract public function getUsers($groupId);

}

?>
