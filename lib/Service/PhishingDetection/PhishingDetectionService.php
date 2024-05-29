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

use Horde_Mime_Headers;
use OCA\Mail\AddressList;
use OCA\Mail\PhishingDetectionList;

class PhishingDetectionService {


	/** @var PhishingDetectionList */
	private $list ;

	public function __construct(private ContactCheck $contactCheck, private CustomEmailCheck $customEmailCheck, private DateCheck $dateCheck, private LinkCheck $linkCheck, private ReplyToCheck $replyToCheck, private TrustedCheck $trustedCheck) {
		$this->contactCheck = $contactCheck;
		$this->customEmailCheck = $customEmailCheck;
		$this->dateCheck = $dateCheck;
		$this->replyToCheck = $replyToCheck;
		$this->trustedCheck = $trustedCheck;
		$this->linkCheck = $linkCheck;
		$this->list = new PhishingDetectionList();
	}



	public function checkHeadersForPhishing(Horde_Mime_Headers $headers, string $uid, bool $hasHtmlMessage, string $htmlMessage): array {
		$result = [];
		$fromFN = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getLabel();
		$fromEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getEmail();
		$replyToEmailHeader = $headers->getHeader('Reply-To')?->getAddressList(true);
		$replyToEmail = isset($replyToEmailHeader)? AddressList::fromHorde($replyToEmailHeader)->first()->getEmail() : null ;
		$date = $headers->getHeader('Date')->__get('value');
		$customEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getCustomEmail();
		$this->list->addCheck($this->replyToCheck->run($fromEmail, $replyToEmail));
		$this->list->addCheck($this->contactCheck->run($fromFN, $fromEmail));
		$this->list->addCheck($this->dateCheck->run($date));
		$this->list->addCheck($this->customEmailCheck->run($fromEmail, $customEmail));
		// $this->trustedCheck->run($uid, $fromEmail);
		if($hasHtmlMessage) {
			$this->list->addCheck($this->linkCheck->run($htmlMessage));
		}
		return $this->list->jsonSerialize();
	}
}
