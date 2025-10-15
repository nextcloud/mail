<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;

class ReplyToCheck {
	protected IL10N $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	public function run(string $fromEmail, ?string $replyToEmail) :PhishingDetectionResult {
		if ($replyToEmail === null) {
			return  new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false);
		}
		if ($fromEmail === $replyToEmail) {
			return new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, false);
		}

		return new PhishingDetectionResult(PhishingDetectionResult::REPLYTO_CHECK, true, $this->l10n->t('Reply-To email: %1$s  is different from the sender email: %2$s', [$replyToEmail, $fromEmail]));

	}

}
