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
	}

	public function checkHeadersForPhishing(Horde_Mime_Headers $headers, bool $hasHtmlMessage, string $htmlMessage = ''): array {
		/** @var string|null $fromFN */
		$fromFN = null;
		/** @var string|null $fromEmail */
		$fromEmail = null;
		/** @var string|null $customEmail */
		$customEmail = null;
		$fromHeader = $headers->getHeader('From');
		if ($fromHeader instanceof Horde_Mime_Headers_Element_Address) {
			$firstAddr = AddressList::fromHorde($fromHeader->getAddressList(true))?->first();
			$fromFN = $firstAddr?->getLabel();
			$fromEmail = $firstAddr?->getEmail();
			$customEmail = $firstAddr?->getCustomEmail();
		}

		/** @var string|null $replyToEmail */
		$replyToEmail = null;
		$replyToHeader = $headers->getHeader('Reply-To');
		if ($replyToHeader instanceof Horde_Mime_Headers_Element_Address) {
			$replyToAddrs = $replyToHeader->getAddressList(true);
			if (isset($replyToAddrs)) {
				$replyToEmail = AddressList::fromHorde($replyToAddrs)->first()?->getEmail();
			}
		}

		$date = $headers->getHeader('Date')?->value;

		$list = new PhishingDetectionList();
		if ($fromEmail !== null) {
			if ($replyToEmail !== null) {
				$list->addCheck($this->replyToCheck->run($fromEmail, $replyToEmail));
			}
			if ($fromFN !== null) {
				$list->addCheck($this->contactCheck->run($fromFN, $fromEmail));
			}
			if ($customEmail !== null) {
				$list->addCheck($this->customEmailCheck->run($fromEmail, $customEmail));
			}
		}
		if ($date !== null) {
			$list->addCheck($this->dateCheck->run($date));
		}
		if ($hasHtmlMessage) {
			$list->addCheck($this->linkCheck->run($htmlMessage));
		}
		return $list->jsonSerialize();
	}
}
