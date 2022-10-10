<?php

declare(strict_types=1);

/**
 * @author Kristian Lebold <kristian@lebold.info>
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

namespace OCA\Mail\Service\ContactIntegration;

use OCA\Mail\Service\ContactsIntegration;

class ContactIntegrationService {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	public function __construct(ContactsIntegration $ci) {
		$this->contactsIntegration = $ci;
	}

	public function findMatches(string $mail): array {
		$matches = $this->contactsIntegration->getContactsWithMail($mail);
		return $matches;
	}

	public function addEMailToContact(string $uid, string $mail): ?array {
		return $this->contactsIntegration->addEmailToContact($uid, $mail);
	}

	public function newContact(string $name, string $mail): ?array {
		return $this->contactsIntegration->newContact($name, $mail);
	}

	public function autoComplete(string $term): array {
		return $this->contactsIntegration->getContactsWithName($term);
	}
}
