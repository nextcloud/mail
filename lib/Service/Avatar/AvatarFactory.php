<?php

declare(strict_types=1);

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\Avatar;

class AvatarFactory {
	/**
	 * Create a new avatar whose URL points to an internal endpoint
	 *
	 * @param string $url
	 * @return Avatar
	 */
	public function createInternal(string $url): Avatar {
		return new Avatar($url, null, false);
	}

	/**
	 * Create a new avatar whose URL points to an external endpoint
	 *
	 * @param string $url
	 * @param string $mime
	 * @return Avatar
	 */
	public function createExternal(string $url, string $mime): Avatar {
		return new Avatar($url, $mime);
	}
}
