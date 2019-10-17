<?php

declare(strict_types=1);

/**
 * @copyright 2019 Pierre Gordon <pierregordon@protonmail.com>
 *
 * @author 2019 Pierre Gordon <pierregordon@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\Sieve;

class ScriptFactory {

	/**
	 * @param string $script
	 * @param string $script_name
	 * @return Script
	 */
	public function createCustom(string $script, string $script_name): Script {
		return new Script(Script::SCRIPT_CUSTOM_BASE_NAME . $script_name, $script);
	}

	/**
	 * @param string $script
	 * @return Script
	 */
	public function createSimple(string $script): Script {
		return new Script(Script::SCRIPT_NAME, $script);
	}
}
