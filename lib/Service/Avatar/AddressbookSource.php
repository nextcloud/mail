<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
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

namespace OCA\Mail\Service\Avatar;

use OCA\Mail\Service\ContactsIntegration;

/**
 * This class is just a think wrapper around the contacts integration to use it
 * as avatar source
 */
class AddressbookSource implements IAvatarSource {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	public function __construct(ContactsIntegration $contactsIntegration) {
		$this->contactsIntegration = $contactsIntegration;
	}

	/**
	 * Does this source query external services?
	 *
	 * @return bool
	 */
	public function isExternal(): bool {
		return false;
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
	public function fetch(string $email, AvatarFactory $factory) {
		$url = $this->contactsIntegration->getPhoto($email);

		if ($url === false || $url === null) {
			return null;
		}

		return $factory->createInternal($url);
	}
}
