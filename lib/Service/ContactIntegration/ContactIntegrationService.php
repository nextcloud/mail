<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
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
