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

use OCA\Mail\Service\Group\AbstractGroupService;

class GroupsIntegration {

	/**
	 * @var IGroupService
	 */
	private $groupServices = [];

	public function __construct(AbstractGroupService ...$groupServices) {
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
				$gid = $this->servicePrefix($gs) . $g['id'];
				$receivers[] = [
					'id' => $gid,
					'label' => $g['name'] . " (" . $gs->getNamespace() . ")",
					'value' => $gid,
					'photo' => null,
				];
			}
		}

		return $receivers;
	}

	/**
	 * Returns the prefix for the group service.
	 *
	 * @param AbstractGroupService $gs
	 * @return string
	 */
	public function servicePrefix(AbstractGroupService $gs) {
		return strtolower($gs->getNamespace()) . ":";
	}

	/** 
	 * Expands a string of group names to its members email addresses.
	 *
	 * @param string $recipients
	 * @return string
	 */
	public function expand($recipients) {
		return 
			array_reduce($this->groupServices, function($carry, $service) {
				return 
					preg_replace_callback(
						'/' . preg_quote($this->servicePrefix($service)) . '([\w\d ]+)(,?)/',
						function($matches) use ($service) {
							if(empty($matches[1])) return "";
							$members = $service->getUsers($matches[1]);
							$addresses = [];
							foreach($members as $m) {
								if(empty($m['email'])) continue;
								$addresses[] = $m['email'];
							}
							return implode(',', $addresses) 
								. (!empty($matches[2]) && !empty($addresses) ? ',' : '');
						},
						$carry
					);
			}, $recipients );

	}

}
