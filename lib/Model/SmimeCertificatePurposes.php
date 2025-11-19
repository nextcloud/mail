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
	public function __construct(
		private bool $sign,
		private bool $encrypt
	) {
	}

	public function hasSign(): bool {
		return $this->sign;
	}

	public function setSign(bool $sign): void {
		$this->sign = $sign;
	}

	public function hasEncrypt(): bool {
		return $this->encrypt;
	}

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
