<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use JsonSerializable;
use ReturnTypeWillChange;

class PhishingDetectionList implements JsonSerializable {

	/** @var PhishingDetectionResult[] */
	private array $checks;

	/**
	 * @param PhishingDetectionResult[] $checks
	 */
	public function __construct(array $checks = []) {
		$this->checks = $checks;
	}

	public function addCheck(PhishingDetectionResult $check): void {
		$this->checks[] = $check;
	}

	private function isWarning(): bool {
		foreach ($this->checks as $check) {
			if (in_array($check->getType(), [PhishingDetectionResult::DATE_CHECK, PhishingDetectionResult::LINK_CHECK, PhishingDetectionResult::CUSTOM_EMAIL_CHECK, PhishingDetectionResult::CONTACTS_CHECK, PhishingDetectionResult::IMAP_FLAG_CHECK]) && $check->isPhishing()) {
				return true;
			}
		}
		return false;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$result = array_map(static fn (PhishingDetectionResult $check) => $check->jsonSerialize(), $this->checks);
		return [
			'checks' => $result,
			'warning' => $this->isWarning(),
		];
	}

}
