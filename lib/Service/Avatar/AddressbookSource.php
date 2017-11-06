<?php

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

	/**
	 * @param ContactsIntegration $contactsIntegration
	 */
	public function __construct(ContactsIntegration $contactsIntegration) {
		$this->contactsIntegration = $contactsIntegration;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return string|null
	 */
	public function fetch($email, $uid) {
		return $this->contactsIntegration->getPhoto($email);
	}

}
