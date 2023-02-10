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
use ReturnTypeWillChange;

class SmimeCertificateInfo implements JsonSerializable {
	private ?string $commonName;
	private ?string $emailAddress;
	private int $notAfter;

	public function __construct(?string $commonName,
								?string $emailAddress,
								int $notAfter) {
		$this->commonName = $commonName;
		$this->emailAddress = $emailAddress;
		$this->notAfter = $notAfter;
	}

	/**
	 * @return string
	 */
	public function getCommonName(): ?string {
		return $this->commonName;
	}

	/**
	 * @param string $commonName
	 */
	public function setCommonName(?string $commonName): void {
		$this->commonName = $commonName;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress(): ?string {
		return $this->emailAddress;
	}

	/**
	 * @param string $emailAddress
	 */
	public function setEmailAddress(?string $emailAddress): void {
		$this->emailAddress = $emailAddress;
	}

	/**
	 * @return int
	 */
	public function getNotAfter(): int {
		return $this->notAfter;
	}

	/**
	 * @param int $notAfter
	 */
	public function setNotAfter(int $notAfter): void {
		$this->notAfter = $notAfter;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'commonName' => $this->commonName,
			'emailAddress' => $this->emailAddress,
			'notAfter' => $this->notAfter,
		];
	}
}
