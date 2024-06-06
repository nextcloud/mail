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

use DateTime;
use OCA\Mail\PhishingDetectionResult;
use OCP\IL10N;

class DateCheck {
	protected IL10N $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	public function run(string $date): PhishingDetectionResult {
		$now = new DateTime();
		$dt = new DateTime($date);
		if ($dt > $now) {
			return new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, true, $this->l10n->t("Sent date is in the future"));
		}
		return new PhishingDetectionResult(PhishingDetectionResult::DATE_CHECK, false);
	}

}
