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
use OCP\IL10N;

class PhishingDetectionService {


	private ContactsIntegration $contactIntegration;
	private ITrustedSenderService $trustedSenderService;
	protected IL10N $l10n;


	private bool $warn = false;


	public function __construct(ContactsIntegration $contactIntegration, ITrustedSenderService $trustedSenderService, IL10N $l10n) {
		$this->contactIntegration = $contactIntegration;
		$this->trustedSenderService = $trustedSenderService;
		$this->l10n = $l10n;
	}

	private function checkDate(string $date): array {
		$now = new DateTime();
		$dt = new DateTime($date);
		if($dt > $now) {
			$this->warn = true;
			return ["check" => false , "message" => $this->l10n->t("Sent date is in the future")];
		}
		return ["check" => $dt < $now ];
	}
	// %1$s is the from email and %2$s is the reply to email

	private function checkReplyTo(string $fromEmail, ?string $replyToEmail): array {
		if(!(isset($replyToEmail))) {
			return ["check" => true];
		}
		if($replyToEmail !== $fromEmail) {
			$this->warn = true;
		}
		return ["check" => false , "message" => $this->l10n->t('Reply-To email: %1$s  is different from the sender email: %2$s', [$replyToEmail, $fromEmail])];
	}

	private function checkCustomEmail(string $fromEmail, ?string $customEmail): array {
		if(!(isset($customEmail))) {
			return ["check" => true];
		}
		if($customEmail !== $fromEmail) {
			$this->warn = true;
		}
		return ["check" => false , "message" => $this->l10n->t('Sender is using a custom email: %1$s instead of the sender email: %2$s', [$customEmail, $fromEmail])] ;
	}


	private function checkContacts(string $fn, string $email):array {
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
			return ["check" => false, "message" => $this->l10n->t('Sender email: %1$s is not in the contacts list, but the sender name: %2$s is in the contacts list with the following emails: %3$s', [$email, $fn, $emails])];
		}
		return ["check" => true];
	}


	private function checkTrusted(string $uid, string $email): array {
		$domain = explode('@', $email)[1];
		$trusted = $this->trustedSenderService->isTrusted($uid, $email) || $this->trustedSenderService->isTrusted($uid, $domain);

		//returns a "trusted" key instead of "check" because we don't want it to be part of the frontend warning messages

		if(!$trusted) {
			return ["trusted" => false, "message" => $this->l10n->t('Sender email: %1$s is not trusted', [$email])];
		}
		return ["trusted" => true];
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

	private function checkAnchorTags(string $htmlMessage): array {

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
			if($this->textLooksLikeALink($zipped['linkText'])) {
				if (str_contains($zipped['linkText'], $zipped['href']) || str_contains($zipped['href'], $zipped['linkText']) === false) {
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
				'message' => $this->l10n->t('Some addresses in this message are not matching the link text'),
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
		$result['replyTo'] = $this->checkReplyTo($fromEmail, $replyToEmail);
		$result['contactCheck'] = $this->checkContacts($fromFN, $fromEmail);
		$result['dateCheck'] = $this->checkDate($date);
		$result['checkCustomEmail'] = $this->checkCustomEmail($fromEmail, $customEmail);
		$result['trustedCheck'] = $this->checkTrusted($uid, $fromEmail);
		if($hasHtmlMessage) {
			$result['links'] = $this->checkAnchorTags($htmlMessage);
		}
		$result['warn'] = $this->warn;
		return $result;
	}
}
