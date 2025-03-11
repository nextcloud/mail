<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;

class CustomEmailCheck {
	protected IL10N $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	public function run(string $fromEmail, ?string $customEmail): PhishingDetectionResult {
		if (!(isset($customEmail))) {
			return new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, false);
		}
		if ($fromEmail === $customEmail) {
			return new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, false);
		}
		return new PhishingDetectionResult(PhishingDetectionResult::CUSTOM_EMAIL_CHECK, true, $this->l10n->t('Sender is using a custom email: %1$s instead of the sender email: %2$s', [$customEmail, $fromEmail]));
	}

}
