<?php

/**
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

namespace OCA\Mail\Service\AvatarSource;

use OCA\Mail\Service\ContactsIntegration;

class AddressbookSource {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	public function __construct(ContactsIntegration $contactsIntegration) {
		$this->contactsIntegration = $contactsIntegration;
	}

	public function fetch($email) {
		if ($this->contactsIntegration->getPhoto($email) === null) {
			return null;
		}

		return [
			'email' => $email,
			'source' => 'addressbook',
			'url' => ''
		];
	}
}
