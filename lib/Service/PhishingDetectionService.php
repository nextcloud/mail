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
use OCA\Mail\Contracts\ITrustedSenderService;

class PhishingDetectionService {


	private ContactsIntegration $contactIntegration;

	private ITrustedSenderService $trustedSenderService;


	private bool $warn = false;


	public function __construct(ContactsIntegration $contactIntegration, ITrustedSenderService $trustedSenderService) {
		$this->contactIntegration = $contactIntegration;
		$this->trustedSenderService = $trustedSenderService;
	}

	private function checkDatePass(string $date): array {
		$now = new DateTime();
		$dt = new DateTime($date);
		if($dt > $now) {
			$this->warn = true;
			return ["check" => false , "message" => "Sent date is in the future"];
		}
		return ["check" => $dt < $now ];
	}

	private function replyToCheckPass(string $fromEmail, ?string $replyToEmail): array {
		if(!(isset($replyToEmail))) {
			return ["check" => true];
		}
		if($replyToEmail !== $fromEmail) {
			$this->warn = true;
		}
		return ["check" => false , "message" => "Reply-To email: ${$replyToEmail} is different from the sender email: ${$fromEmail}"];
	}

	private function customEmailCheck(string $fromEmail, ?string $customEmail): array {
		if(!(isset($customEmail))) {
			return ["check" => true];
		}
		if($customEmail !== $fromEmail) {
			$this->warn = true;
		}
		return ["check" => false , "message" => "Sender is using a custom email: ${$customEmail} instead of the sender email: ${$fromEmail}"] ;
	}


	private function contactsCheckPass(string $fn, string $email):array {
		$emailInContacts = false;
		$emails = "";
		$contacts = $this->contactIntegration->getContactsWithName($fn, true);
		foreach ($contacts as $contact) {
			foreach ($contact['email'] as $contactEmail) {
				$emailInContacts = true;
				$emails .= $contactEmail.",";
				if ($contactEmail === $email) {
					return ["check" => true];
				}
			}
		}
		if ($emailInContacts) {
			$this->warn = true;
			return ["check" => false, "message" => "Sender email: ${$email} is not in the contacts list, but the sender name: ${$fn} is in the contacts list with the following emails: ${$emails}"];
		}
		return ["check" => true];
	}

	private function checkTrusted(string $uid, string $email): array {
		$domain = explode('@', $email)[1];
		$trusted = $this->trustedSenderService->isTrusted($uid, $email) || $this->trustedSenderService->isTrusted($uid, $domain);

		if(!$trusted) {
			$this->warn = true;
			return ["check" => false, "message" => "Sender email: ${$email} is not trusted"];
		}
		return ["check" => true];
	}

	private function isLink(string $text): bool {
		$pattern = '/^(https?:\/\/|www\.|[a-zA-Z0-9-]+\.[a-zA-Z]{2,})/i';

		return preg_match($pattern, $text) === 1;
	}

	private function getInnerText(\DOMElement $node) : string {
		$innerText = '';
		foreach ($node->childNodes as $child) {
			if ($child->nodeType === XML_TEXT_NODE) {
				$innerText .= $child->nodeValue;
			} elseif ($child->nodeType === XML_ELEMENT_NODE) {
				$innerText .= $this->getInnerText($child);
			}
		}
		return $innerText;
	}

	private function parseAnchorTags(string $htmlMessage): array {

		$results = [];
		$zippedArray = [];

		$dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($htmlMessage);
		libxml_use_internal_errors();
		$anchors = $dom->getElementsByTagName('a');
		foreach ($anchors as $anchor) {
			$href = $anchor->getAttribute('href');
			$linkText = $this->getInnerText($anchor);
			$zippedArray[] = [
				'href' => $href,
				'linkText' => $linkText
			];
		}
		foreach ($zippedArray as $zipped) {
			if($this->isLink($zipped['linkText'])) {
				if (str_contains($zipped['linkText'], $zipped['href']) === false) {
					$results[] = [
						'href' => $zipped['href'],
						'linkText' => $zipped['linkText'],
					];
				}
			}
		}
		if(count($results) > 0) {
			$this->warn = true;
			return [
				'check' => false,
				'message' => 'Some addresses in this message are not matching the link text',
				'links' => $results
			];
		}
		return [
			'check' => true];

	}


	public function checkHeadersForPhishing(Horde_Mime_Headers $headers, string $uid, bool $hasHtmlMessage, string $htmlMessage): array {
		$result = [];
		$fromFN = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getLabel();
		$fromEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getEmail();
		$replyToEmailHeader = $headers->getHeader('Reply-To')?->getAddressList(true);
		$replyToEmail = isset($replyToEmailHeader)? AddressList::fromHorde($replyToEmailHeader)->first()->getEmail() : null ;
		$date = $headers->getHeader('Date')->__get('value');
		$customEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getCustomEmail();
		$result['replyTo'] = $this->replyToCheckPass($fromEmail, $replyToEmail);
		$result['contactCheck'] = $this->contactsCheckPass($fromFN, $fromEmail);
		$result['dateCheck'] = $this->checkDatePass($date);
		$result['customEmailCheck'] = $this->customEmailCheck($fromEmail, $customEmail);
		$result['trustedCheck'] = $this->checkTrusted($uid, $fromEmail);
		if($hasHtmlMessage) {
			$result['links'] = $this->parseAnchorTags($htmlMessage);
		}
		$result['warn'] = $this->warn;
		return $result;
	}
}
