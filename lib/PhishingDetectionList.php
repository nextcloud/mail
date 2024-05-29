<?php

declare(strict_types=1);

/**
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
 *
 */

namespace OCA\Mail;

use JsonSerializable;
use ReturnTypeWillChange;

/**
 * @psalm-immutable
 */
class PhishingDetectionList implements JsonSerializable {

	/** @var PhishingDetectionResult[] */
	private $checks;

	private bool $warning = false;

	public function __construct(array $checks = []) {
		$this->checks = $checks;
	}

	public function addCheck(PhishingDetectionResult $check) {
		$this->checks[] = $check;
	}
	private function isWarning() {
		foreach ($this->checks as $check) {
			if (in_array($check->getType(), [PhishingDetectionResult::DATE_CHECK, PhishingDetectionResult::LINK_CHECK, PhishingDetectionResult::CUSTOM_EMAIL_CHECK, PhishingDetectionResult::CONTACTS_CHECK]) && $check->isPhishing()) {
				return true;
			}
		}
		return false;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$result = array_map(static function (PhishingDetectionResult $check) {
			return $check->jsonSerialize();
		}, $this->checks);
		return [
			'checks' => $result,
			'warning' => $this->isWarning(),
		];
	}

}
