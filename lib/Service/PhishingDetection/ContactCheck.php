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
	public function __construct(
		private ContactsIntegration $contactIntegration,
		private IL10N $l10n,
	) {
		$this->l10n = $l10n;
		$this->contactIntegration = $contactIntegration;
	}

	public function run(string $fn, string $email): PhishingDetectionResult {
		$emails = [];
		$contacts = $this->contactIntegration->getContactsWithName($fn);
		foreach ($contacts as $contact) {
			if (!isset($contact['email'])) {
				continue;
			}
			foreach ($contact['email'] as $contactEmail) {
				$emails[] = $contactEmail;
				if (strcasecmp($contactEmail, $email) == 0) {
					return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
				}
			}
		}
		if (count($emails) == 1) {
			return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, true, $this->l10n->t('Sender email: %1$s is not in the address book, but the sender name: %2$s is in the address book with the following email: %3$s', [$email, $fn, $emails[0]]));
		} elseif (count($emails) > 1) {
			return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, true, $this->l10n->t('Sender email: %1$s is not in the address book, but the sender name: %2$s is in the address book with the following emails: %3$s', [$email, $fn, implode(', ', $emails)]));
		}

		return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
	}

}
