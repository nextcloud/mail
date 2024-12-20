<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use Horde_Mime_Headers;
use Horde_Mime_Headers_Element_Address;
use OCA\Mail\AddressList;
use OCA\Mail\PhishingDetectionList;

class PhishingDetectionService {
	public function __construct(
		private ContactCheck $contactCheck,
		private CustomEmailCheck $customEmailCheck,
		private DateCheck $dateCheck,
		private ReplyToCheck $replyToCheck,
		private LinkCheck $linkCheck,
	) {
		$this->contactCheck = $contactCheck;
		$this->customEmailCheck = $customEmailCheck;
		$this->dateCheck = $dateCheck;
		$this->replyToCheck = $replyToCheck;
		$this->linkCheck = $linkCheck;
	}


	public function checkHeadersForPhishing(Horde_Mime_Headers $headers, bool $hasHtmlMessage, string $htmlMessage = ''): array {
		$list = new PhishingDetectionList();
		$fromHeader = $headers->getHeader('From');
		$sender = AddressList::fromHorde($fromHeader->getAddressList(true))->first();
		$fromFN = $sender?->getLabel();
		$fromEmail = $sender?->getEmail();
		$replyToHeader = $headers->getHeader('Reply-To');
		if ($replyToHeader instanceof Horde_Mime_Headers_Element_Address) {
			$replyToEmailHeader = $replyToHeader->getAddressList(true);
			$replyToEmail = AddressList::fromHorde($replyToEmailHeader)->first()?->getEmail();
		} else {
			$replyToEmail = null;
		}
		$date = $headers->getHeader('Date')->__get('value');
		$customEmail = $sender?->getCustomEmail();
		if ($fromEmail !== null) {
			$list->addCheck($this->replyToCheck->run($fromEmail, $replyToEmail));
		}
		if ($fromFN !== null) {
			$list->addCheck($this->contactCheck->run($fromFN, $fromEmail));
		}
		if (is_string($date)) {
			$list->addCheck($this->dateCheck->run($date));
		}
		if ($fromEmail !== null) {
			$list->addCheck($this->customEmailCheck->run($fromEmail, $customEmail));
		}
		if ($hasHtmlMessage) {
			$list->addCheck($this->linkCheck->run($htmlMessage));
		}
		return $list->jsonSerialize();
	}
}
