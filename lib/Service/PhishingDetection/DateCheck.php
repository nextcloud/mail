<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\PhishingDetection;

use OCA\Mail\PhishingDetectionResult;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;

class DateCheck {
	protected IL10N $l10n;
	protected ITimeFactory $timeFactory;

	public function __construct(IL10N $l10n, ITimeFactory $timeFactory) {
		$this->l10n = $l10n;
		$this->timeFactory = $timeFactory;
	}

	public function run(string $date): PhishingDetectionResult {
		$now = $this->timeFactory->getDateTime('now');
		try {
			$dt = $this->timeFactory->getDateTime($date);
		} catch (\Exception $e) {
			return new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false);
		}
		if ($dt > $now) {
			return new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, true, $this->l10n->t('Sent date is in the future'));
		}
		return new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false);
	}

}
