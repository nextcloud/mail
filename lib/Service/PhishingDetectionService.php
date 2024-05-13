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

namespace OCA\Mail\Service;

use DateTime;
use Horde_Mime_Headers;
use OCA\Mail\AddressList;
use OCA\Mail\ReceivedList;

class PhishingDetectionService {


	private ContactsIntegration $contactIntegration;

	private bool $warn = false;


	public function __construct(ContactsIntegration $contactIntegration) {
		$this->contactIntegration = $contactIntegration;
	}

	private function checkDatePass(string $date): bool {
		$now = new DateTime();
		$dt = new DateTime($date);
		if($dt > $now) {
			$this->warn = true;
		}
		return $dt < $now;
	}

	private function replyToCheckPass(string $fromEmail, string $replyToEmail): bool {
		if($replyToEmail !== $fromEmail){
			$this->warn = true;
		}
		return $replyToEmail === $fromEmail;
	}

	private function customEmailCheck(string $fromEmail, string $customEmail): bool {
		if($customEmail !== $fromEmail){
			$this->warn = true;
		}
		return $customEmail === $fromEmail;
	}


	private function contactsCheckPass(string $fn, string $email):bool {
		$emailInContacts = false;
		$contacts = $this->contactIntegration->getContactsWithName($fn, true);
		foreach ($contacts as $contact) {
			foreach ($contact['email'] as $contactEmail) {
				$emailInContacts = true;
				if ($contactEmail === $email) {
					return true;
				}
			}
		}
		if ($emailInContacts) {
			$this->warn = true;
			return false;
		}
		return true;
	}


	public function checkHeadersForPhishing(Horde_Mime_Headers $headers): array {
		$result = [];
		$fromFN = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getLabel();
		$fromEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getEmail();
		$replyToEmail = AddressList::fromHorde($headers->getHeader('Reply-To')->getAddressList(true))->first()->getEmail();
		$date = $headers->getHeader('Date')->__get('value');
		$customEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getCustomEmail();
		$result['replyTo'] =$this->replyToCheckPass($fromEmail, $replyToEmail);
		$result['contactCheck'] = $this->contactsCheckPass($fromFN, $fromEmail);
		$result['dateCheck'] = $this->checkDatePass($date);
		$result['customEmailCheck'] = $this->customEmailCheck($fromEmail, $customEmail);
		$result['warn'] = $this->warn;
		return $result;
	}
}
