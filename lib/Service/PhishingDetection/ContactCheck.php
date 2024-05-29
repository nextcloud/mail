<?php

declare(strict_types=1);

/*
 * @copyright 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author 2024 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
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
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\PhishingDetectionResult;
use OCA\Mail\Service\ContactsIntegration;
use OCP\IL10N;

class ContactCheck {
	protected IL10N $l10n;
	private ContactsIntegration $contactIntegration;


	public function __construct(ContactsIntegration $contactIntegration, IL10N $l10n) {
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
			return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, true, $this->l10n->t('Sender email: %1$s is not in the contacts list, but the sender name: %2$s is in the contacts list with the following emails: %3$s', [$email, $fn, $emails]));
		}

		return new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
	}

}
