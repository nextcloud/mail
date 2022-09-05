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

/**
 * Composition of all avatar sources for easier usage
 */
class CompositeAvatarSource {
	/** @var IAvatarSource[] */
	private $sources;

	public function __construct(AddressbookSource $addressbookSource,
								FaviconSource $faviconSource,
								GravatarSource $gravatarSource) {
		// This determines the priority of known sources
		$this->sources = [
			$addressbookSource,
			$gravatarSource,
			$faviconSource,
		];
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @param bool $queryExternal
	 * @return Avatar|null avatar URL if one can be found
	 */
	public function fetch(string $email, AvatarFactory $factory, bool $queryExternal) {
		foreach ($this->sources as $source) {
			if (!$queryExternal && $source->isExternal()) {
				// Skip this one
				continue;
			}

			$avatar = $source->fetch($email, $factory);

			if (is_null($avatar)) {
				continue;
			}

			return $avatar;
		}

		return null;
	}
}
