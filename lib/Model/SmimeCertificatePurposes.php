<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use ReturnTypeWillChange;

final class SmimeCertificatePurposes implements JsonSerializable {
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

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'sign' => $this->sign,
			'encrypt' => $this->encrypt,
		];
	}
}
