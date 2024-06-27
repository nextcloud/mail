<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\PhishingDetectionResult;
use OCA\Mail\Service\ContactsIntegration;
use OCP\IL10N;

class ContactCheck {
	public function __construct(private ContactsIntegration $contactIntegration, private IL10N $l10n) {
		$this->l10n = $l10n;
		$this->contactIntegration = $contactIntegration;
	}

	public function run(string $fn, string $email): PhishingDetectionResult {
		$emailInContacts = false;
		$emails = "";
		$contacts = $this->contactIntegration->getContactsWithName($fn, true);
		foreach ($contacts as $contact) {
			foreach ($contact['email'] as $contactEmail) {
				$emailInContacts = true;
				$emails .= $contactEmail.",";
				if ($contactEmail === $email) {
					return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
				}
			}
		}
		if ($emailInContacts) {
			return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, true, $this->l10n->t('Sender email: %1$s is not in the address book, but the sender name: %2$s is in the address book with the following emails: %3$s', [$email, $fn, $emails]));
		}

		return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
	}

}
