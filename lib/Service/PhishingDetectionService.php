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
use URL\Normalizer;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\ITrustedSenderService;
use OCA\Mail\PhishingDetectionList;
use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;

class PhishingDetectionService {

	private ContactsIntegration $contactIntegration;
	private ITrustedSenderService $trustedSenderService;
	protected IL10N $l10n;

	/** @var PhishingDetectionList */
	private $list ;

	public function __construct(ContactsIntegration $contactIntegration, ITrustedSenderService $trustedSenderService, IL10N $l10n) {
		$this->contactIntegration = $contactIntegration;
		$this->trustedSenderService = $trustedSenderService;
		$this->l10n = $l10n;
		$this->list = new PhishingDetectionList();
	}

	private function checkDate(string $date) {
		$now = new DateTime();
		$dt = new DateTime($date);
		if($dt > $now) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, true, $this->l10n->t("Sent date is in the future"));
		}
		$result = new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false);
		$this->list->addCheck($result);
	}
	// %1$s is the from email and %2$s is the reply to email

	private function checkReplyTo(string $fromEmail, ?string $replyToEmail) {
		if(!(isset($replyToEmail))) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false);
		}
		elseif($fromEmail === $replyToEmail) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false);
		}
		else {
			$result = new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, true, $this->l10n->t('Reply-To email: %1$s  is different from the sender email: %2$s', [$replyToEmail, $fromEmail]));
		}
		$this->list->addCheck($result);
	}

	private function checkCustomEmail(string $fromEmail, ?string $customEmail) {
		if(!(isset($customEmail))) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, false);
		}
		elseif($fromEmail === $customEmail) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, false);
		}
		else {
			$result = new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, true, $this->l10n->t('Sender is using a custom email: %1$s instead of the sender email: %2$s', [$customEmail, $fromEmail]));
		}
		$this->list->addCheck($result);
	}


	private function checkContacts(string $fn, string $email) {
		$emailInContacts = false;
		$check = false;
		$emails = "";
		$contacts = $this->contactIntegration->getContactsWithName($fn, true);
		foreach ($contacts as $contact) {
			foreach ($contact['email'] as $contactEmail) {
				$emailInContacts = true;
				$emails .= $contactEmail.",";
				if ($contactEmail === $email) {
					$check = true;
				}
			}
		}
		if ($emailInContacts && !$check) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, true, $this->l10n->t('Sender email: %1$s is not in the contacts list, but the sender name: %2$s is in the contacts list with the following emails: %3$s', [$email, $fn, $emails]));
		}
		else {
			$result = new PhishingDetectionResult(PhishingDetectionResult::CONTACTS_CHECK, false);
		}
		$this->list->addCheck($result);
	}


	private function checkTrusted(string $uid, string $email) {
		$domain = explode('@', $email)[1];
		$trusted = $this->trustedSenderService->isTrusted($uid, $email) || $this->trustedSenderService->isTrusted($uid, $domain);

		//returns a "trusted" key instead of "check" because we don't want it to be part of the frontend warning messages
		
		if(!$trusted) {
			$result = new PhishingDetectionResult(PhishingDetectionResult::TRUSTED_CHECK, true, $this->l10n->t('Sender email: %1$s is not trusted', [$email]));
		}
		else {
			$result = new PhishingDetectionResult(PhishingDetectionResult::TRUSTED_CHECK, false);
		}
		// Will be fixed in a followup 
		// $this->list->addCheck($result);
	}

	// checks if link text is meant to look like a link
	private function textLooksLikeALink(string $text): bool {
		$pattern = '/(?i)\b((?:https?:(?:\/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b\/?(?!@)))/';

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

	private function checkAnchorTags(string $htmlMessage) {

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
			$un = new Normalizer($zipped['href']);
			$url=  $un->normalize();
			if($this->textLooksLikeALink($zipped['linkText'])) {
				if (str_contains($zipped['linkText'], $url) || str_contains($url, $zipped['linkText']) === false) {
					$results[] = [
						'href' => $url,
						'linkText' => $zipped['linkText'],
					];
				}
			}
		}
		if(count($results) > 0) {
			$results = new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, true, $this->l10n->t('Some addresses in this message are not matching the link text'), $results);
		}
		else {
			$results = new PhishingDetectionResult(PhishingDetectionResult::LINK_CHECK, false);
		}
		$this->list->addCheck($results);

	}


	public function checkHeadersForPhishing(Horde_Mime_Headers $headers, string $uid, bool $hasHtmlMessage, string $htmlMessage): array {
		$result = [];
		$fromFN = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getLabel();
		$fromEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getEmail();
		$replyToEmailHeader = $headers->getHeader('Reply-To')?->getAddressList(true);
		$replyToEmail = isset($replyToEmailHeader)? AddressList::fromHorde($replyToEmailHeader)->first()->getEmail() : null ;
		$date = $headers->getHeader('Date')->__get('value');
		$customEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getCustomEmail();
		$this->checkReplyTo($fromEmail, $replyToEmail);
		$this->checkContacts($fromFN, $fromEmail);
		$this->checkDate($date);
		$this->checkCustomEmail($fromEmail, $customEmail);
		$this->checkTrusted($uid, $fromEmail);
		if($hasHtmlMessage) {
			$this->checkAnchorTags($htmlMessage);
		}
		return $this->list->jsonSerialize();
	}
}
