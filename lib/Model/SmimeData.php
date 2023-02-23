<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
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

class SmimeData implements JsonSerializable {
	private bool $isSigned;
	private ?bool $signatureIsValid;
	private bool $isEncrypted;

	public function __construct() {
		$this->isSigned = false;
		$this->signatureIsValid = null;
		$this->isEncrypted = false;
	}

	/**
	 * @return bool
	 */
	public function isSigned(): bool {
		return $this->isSigned;
	}

	/**
	 * @param bool $isSigned
	 */
	public function setIsSigned(bool $isSigned): void {
		$this->isSigned = $isSigned;
	}

	/**
	 * @return bool|null
	 */
	public function getSignatureIsValid(): ?bool {
		return $this->signatureIsValid;
	}

	/**
	 * @param bool|null $signatureIsValid
	 */
	public function setSignatureIsValid(?bool $signatureIsValid): void {
		$this->signatureIsValid = $signatureIsValid;
	}

	/**
	 * @return bool
	 */
	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}

	/**
	 * @param bool $isEncrypted
	 * @return void
	 */
	public function setIsEncrypted(bool $isEncrypted): void {
		$this->isEncrypted = $isEncrypted;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'isSigned' => $this->isSigned,
			'signatureIsValid' => $this->signatureIsValid,
			'isEncrypted' => $this->isEncrypted,
		];
	}
}
