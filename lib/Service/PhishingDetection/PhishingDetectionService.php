<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use Horde_Mime_Headers;
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
		/** @psalm-suppress UndefinedMethod */
		$fromFN = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getLabel();
		/** @psalm-suppress UndefinedMethod */
		$fromEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getEmail();
		/** @psalm-suppress UndefinedMethod */
		$replyToEmailHeader = $headers->getHeader('Reply-To')?->getAddressList(true);
		$replyToEmail = isset($replyToEmailHeader)? AddressList::fromHorde($replyToEmailHeader)->first()->getEmail() : null ;
		$date = $headers->getHeader('Date')->__get('value');
		/** @psalm-suppress UndefinedMethod */
		$customEmail = AddressList::fromHorde($headers->getHeader('From')->getAddressList(true))->first()->getCustomEmail();
		$list->addCheck($this->replyToCheck->run($fromEmail, $replyToEmail));
		$list->addCheck($this->contactCheck->run($fromFN, $fromEmail));
		$list->addCheck($this->dateCheck->run($date));
		$list->addCheck($this->customEmailCheck->run($fromEmail, $customEmail));
		if ($hasHtmlMessage) {
			$list->addCheck($this->linkCheck->run($htmlMessage));
		}
		return $list->jsonSerialize();
	}
}
