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

interface IGroupService {

	/**
	 * Search the service's groups.
	 *
   * @param string $term
	 * @return string
	 */
	public function search($term);

	/**
	 * Get the group's namespace.
	 *
	 * @return string
	 */
	public function getNamespace();

	/**
	 * Get the group's namespace id.
	 *
	 * @return string
	 */
	public function getNamespaceId();
}

?>
