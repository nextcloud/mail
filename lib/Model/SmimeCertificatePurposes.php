<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use ReturnTypeWillChange;

class SmimeCertificatePurposes implements JsonSerializable {
	private bool $sign;
	private bool $encrypt;

	public function __construct(bool $sign, bool $encrypt) {
		$this->sign = $sign;
		$this->encrypt = $encrypt;
	}

	/**
	 * @return bool
	 */
	public function hasSign(): bool {
		return $this->sign;
	}

	/**
	 * @param bool $sign
	 */
	public function setSign(bool $sign): void {
		$this->sign = $sign;
	}

	/**
	 * @return bool
	 */
	public function hasEncrypt(): bool {
		return $this->encrypt;
	}

	/**
	 * @param bool $encrypt
	 */
	public function setEncrypt(bool $encrypt): void {
		$this->encrypt = $encrypt;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'sign' => $this->sign,
			'encrypt' => $this->encrypt,
		];
	}
}
